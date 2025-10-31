<?php

namespace ReturnsPortal\Services;

use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;
use ReturnsPortal\Repositories\ReturnRepository;
use ReturnsPortal\Models\ReturnModel;
use ReturnsPortal\Events\ReturnCreated;
use ReturnsPortal\Events\ReturnStatusChanged;
use ReturnsPortal\Events\ReturnApproved;
use ReturnsPortal\Events\ReturnReceived;

/**
 * Class ReturnService
 * @package ReturnsPortal\Services
 */
class ReturnService
{
    use Loggable;

    /**
     * @var ReturnRepository
     */
    private $returnRepository;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * @var ConfigRepository
     */
    private $config;

    /**
     * @var EmailService
     */
    private $emailService;

    /**
     * ReturnService constructor.
     */
    public function __construct(
        ReturnRepository $returnRepository,
        OrderRepositoryContract $orderRepository,
        ConfigRepository $config,
        EmailService $emailService
    ) {
        $this->returnRepository = $returnRepository;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->emailService = $emailService;
    }

    /**
     * Create a new return
     * @param array $data
     * @return ReturnModel
     */
    public function createReturn(array $data): ReturnModel
    {
        try {
            // Create return
            $returnRecord = $this->returnRepository->create($data);

            // Create return items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $itemData['return_id'] = $returnRecord->id;
                    $this->returnRepository->createReturnItem($itemData);
                }
            }

            // Fire event
            pluginApp(ReturnCreated::class, ['return' => $returnRecord])->fire();

            // Send email notification
            if ($this->config->get('ReturnsPortal.sendEmailNotifications', true)) {
                $this->emailService->sendReturnCreatedEmail($returnRecord);
            }

            $this->getLogger(__METHOD__)->info('ReturnsPortal::Return created', [
                'returnId' => $returnRecord->id,
                'returnNumber' => $returnRecord->return_number
            ]);

            return $returnRecord;

        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ReturnsPortal::Error creating return', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Approve a return
     * @param int $returnId
     * @param string $adminNote
     * @return array
     */
    public function approveReturn(int $returnId, string $adminNote = ''): array
    {
        try {
            $returnRecord = $this->returnRepository->findById($returnId);

            if (!$returnRecord) {
                return ['success' => false, 'message' => 'Return not found'];
            }

            if ($returnRecord->status !== ReturnModel::STATUS_PENDING) {
                return ['success' => false, 'message' => 'Only pending returns can be approved'];
            }

            $returnRecord->status = ReturnModel::STATUS_APPROVED;
            $returnRecord->admin_notes = $adminNote;
            $returnRecord->approved_at = date('Y-m-d H:i:s');
            
            $this->returnRepository->update($returnRecord);

            // Create status history entry
            $this->createStatusHistory($returnId, ReturnModel::STATUS_APPROVED, $adminNote);

            // Fire event
            pluginApp(ReturnApproved::class, ['return' => $returnRecord])->fire();

            // Send email
            if ($this->config->get('ReturnsPortal.sendEmailNotifications', true)) {
                $this->emailService->sendReturnApprovedEmail($returnRecord);
            }

            $this->getLogger(__METHOD__)->info('ReturnsPortal::Return approved', [
                'returnId' => $returnId
            ]);

            return ['success' => true, 'return' => $returnRecord];

        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ReturnsPortal::Error approving return', [
                'returnId' => $returnId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reject a return
     * @param int $returnId
     * @param string $rejectionReason
     * @param string $adminNote
     * @return array
     */
    public function rejectReturn(int $returnId, string $rejectionReason, string $adminNote = ''): array
    {
        try {
            $returnRecord = $this->returnRepository->findById($returnId);

            if (!$returnRecord) {
                return ['success' => false, 'message' => 'Return not found'];
            }

            if ($returnRecord->isCompleted()) {
                return ['success' => false, 'message' => 'Cannot reject completed return'];
            }

            $returnRecord->status = ReturnModel::STATUS_REJECTED;
            $returnRecord->rejection_reason = $rejectionReason;
            $returnRecord->admin_notes = $adminNote;
            
            $this->returnRepository->update($returnRecord);

            // Create status history
            $this->createStatusHistory($returnId, ReturnModel::STATUS_REJECTED, $adminNote);

            // Send email
            if ($this->config->get('ReturnsPortal.sendEmailNotifications', true)) {
                $this->emailService->sendReturnRejectedEmail($returnRecord, $rejectionReason);
            }

            $this->getLogger(__METHOD__)->info('ReturnsPortal::Return rejected', [
                'returnId' => $returnId,
                'reason' => $rejectionReason
            ]);

            return ['success' => true];

        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ReturnsPortal::Error rejecting return', [
                'returnId' => $returnId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mark return as received
     * @param int $returnId
     * @param array $itemConditions
     * @param string $qualityNotes
     * @return array
     */
    public function markAsReceived(int $returnId, array $itemConditions, string $qualityNotes = ''): array
    {
        try {
            $returnRecord = $this->returnRepository->findById($returnId);

            if (!$returnRecord) {
                return ['success' => false, 'message' => 'Return not found'];
            }

            $returnRecord->status = ReturnModel::STATUS_RECEIVED;
            $returnRecord->received_at = date('Y-m-d H:i:s');
            
            if (!empty($qualityNotes)) {
                $returnRecord->admin_notes .= "\n\nQuality Notes: " . $qualityNotes;
            }
            
            $this->returnRepository->update($returnRecord);

            // Update item conditions
            if (!empty($itemConditions)) {
                $this->updateItemConditions($returnId, $itemConditions);
            }

            // Create status history
            $this->createStatusHistory($returnId, ReturnModel::STATUS_RECEIVED, $qualityNotes);

            // Fire event
            pluginApp(ReturnReceived::class, ['return' => $returnRecord])->fire();

            // Send email
            if ($this->config->get('ReturnsPortal.sendEmailNotifications', true)) {
                $this->emailService->sendReturnReceivedEmail($returnRecord);
            }

            return ['success' => true];

        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ReturnsPortal::Error marking return as received', [
                'returnId' => $returnId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get return reasons from config
     * @return array
     */
    public function getReturnReasons(): array
    {
        $reasons = $this->config->get('ReturnsPortal.returnReasons', []);
        return is_array($reasons) ? $reasons : [];
    }

    /**
     * Get status label
     * @param string $status
     * @return string
     */
    public function getStatusLabel(string $status): string
    {
        $labels = [
            ReturnModel::STATUS_PENDING => 'Pending Approval',
            ReturnModel::STATUS_APPROVED => 'Approved',
            ReturnModel::STATUS_REJECTED => 'Rejected',
            ReturnModel::STATUS_SHIPPED => 'Shipped Back',
            ReturnModel::STATUS_RECEIVED => 'Received',
            ReturnModel::STATUS_INSPECTING => 'Under Inspection',
            ReturnModel::STATUS_REFUNDED => 'Refunded',
            ReturnModel::STATUS_COMPLETED => 'Completed',
            ReturnModel::STATUS_CANCELLED => 'Cancelled'
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Get available statuses
     * @return array
     */
    public function getAvailableStatuses(): array
    {
        $statuses = ReturnModel::getStatuses();
        $result = [];
        
        foreach ($statuses as $status) {
            $result[] = [
                'value' => $status,
                'label' => $this->getStatusLabel($status)
            ];
        }
        
        return $result;
    }

    /**
     * Get available actions for a return
     * @param ReturnModel $returnRecord
     * @return array
     */
    public function getAvailableActions(ReturnModel $returnRecord): array
    {
        $actions = [];

        if ($returnRecord->status === ReturnModel::STATUS_PENDING) {
            $actions[] = 'approve';
            $actions[] = 'reject';
        }

        if ($returnRecord->status === ReturnModel::STATUS_APPROVED) {
            $actions[] = 'mark_shipped';
        }

        if (in_array($returnRecord->status, [ReturnModel::STATUS_SHIPPED, ReturnModel::STATUS_APPROVED])) {
            $actions[] = 'mark_received';
        }

        if ($returnRecord->canBeRefunded()) {
            $actions[] = 'refund';
        }

        if ($returnRecord->canBeCancelled()) {
            $actions[] = 'cancel';
        }

        return $actions;
    }

    /**
     * Get return status history
     * @param int $returnId
     * @return array
     */
    public function getReturnStatusHistory(int $returnId): array
    {
        // This would query a status_history table
        // For now, return basic info
        return [];
    }

    /**
     * Create status history entry
     * @param int $returnId
     * @param string $status
     * @param string $notes
     */
    private function createStatusHistory(int $returnId, string $status, string $notes = ''): void
    {
        // Would save to status_history table
        // Implementation depends on your needs
    }

    /**
     * Update item conditions
     * @param int $returnId
     * @param array $conditions
     */
    private function updateItemConditions(int $returnId, array $conditions): void
    {
        // Update return item conditions based on inspection
    }

    /**
     * Get statistics
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->returnRepository->getStatistics();
    }

    /**
     * Get detailed statistics
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getDetailedStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        // Implementation for detailed stats with date filters
        $stats = $this->getStatistics();
        
        // Add more detailed metrics
        $stats['returnRate'] = 0; // Calculate from orders
        $stats['averageProcessingTime'] = 0; // Calculate average
        $stats['topReturnReasons'] = []; // Aggregate by reason
        
        return $stats;
    }

    /**
     * Export returns data
     * @param array $filters
     * @param string $format
     * @return array
     */
    public function exportReturns(array $filters, string $format = 'csv'): array
    {
        $returns = $this->returnRepository->search($filters, 1, 10000);
        
        // Generate export filename
        $filename = 'returns_export_' . date('Y-m-d_His') . '.' . $format;
        
        // Create CSV content as string (no file writing)
        if ($format === 'csv') {
            $csv = [];
            
            // Headers
            $csv[] = implode(',', [
                '"Return Number"', '"Order ID"', '"Customer"', '"Email"', '"Status"',
                '"Amount"', '"Created"', '"Updated"'
            ]);
            
            // Data rows
            foreach ($returns['data'] as $returnRecord) {
                $csv[] = implode(',', [
                    '"' . str_replace('"', '""', $returnRecord->return_number) . '"',
                    '"' . $returnRecord->order_id . '"',
                    '"' . str_replace('"', '""', $returnRecord->customer_name) . '"',
                    '"' . str_replace('"', '""', $returnRecord->customer_email) . '"',
                    '"' . $returnRecord->status . '"',
                    '"' . $returnRecord->total_amount . '"',
                    '"' . $returnRecord->created_at . '"',
                    '"' . $returnRecord->updated_at . '"'
                ]);
            }
            
            $csvContent = implode("\n", $csv);
        }
        
        return [
            'content' => $csvContent ?? '',
            'filename' => $filename,
            'mimeType' => 'text/csv'
        ];
    }

    /**
     * Upload return image
     * @param $file
     * @return string
     */
    public function uploadReturnImage($file): string
    {
        // Implementation for image upload
        // Would save to storage and return URL
        return '/path/to/uploaded/image.jpg';
    }

    /**
     * Generate return label
     * @param ReturnModel $returnRecord
     * @return array
     */
    public function generateReturnLabel(ReturnModel $returnRecord): array
    {
        // Generate shipping label data
        return [
            'labelUrl' => '/path/to/label.pdf',
            'trackingNumber' => $returnRecord->tracking_number
        ];
    }

    /**
     * Generate barcode for return number
     * @param string $returnNumber
     * @return string
     */
    public function generateBarcode(string $returnNumber): string
    {
        // Generate barcode image/data
        return base64_encode($returnNumber);
    }
}
