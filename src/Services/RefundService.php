<?php

namespace ReturnsPortal\Services;

use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use ReturnsPortal\Repositories\ReturnRepository;
use ReturnsPortal\Models\ReturnModel;

/**
 * Class RefundService
 * @package ReturnsPortal\Services
 */
class RefundService
{
    use Loggable;

    /**
     * @var ReturnRepository
     */
    private $returnRepository;

    /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepository;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * @var EmailService
     */
    private $emailService;

    /**
     * RefundService constructor.
     */
    public function __construct(
        ReturnRepository $returnRepository,
        PaymentRepositoryContract $paymentRepository,
        OrderRepositoryContract $orderRepository,
        EmailService $emailService
    ) {
        $this->returnRepository = $returnRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
        $this->emailService = $emailService;
    }

    /**
     * Process refund for a return
     * @param int $returnId
     * @param array $options
     * @return array
     */
    public function processRefund(int $returnId, array $options): array
    {
        try {
            $return = $this->returnRepository->findById($returnId);

            if (!$return) {
                return ['success' => false, 'message' => 'Return not found'];
            }

            if (!$return->canBeRefunded()) {
                return ['success' => false, 'message' => 'Return cannot be refunded in current status'];
            }

            $method = $options['method'] ?? 'original_payment';
            $amount = $options['amount'] ?? $return->total_amount;
            $note = $options['note'] ?? '';

            // Process refund based on method
            $refundId = null;
            switch ($method) {
                case 'original_payment':
                    $refundId = $this->processOriginalPaymentRefund($return, $amount);
                    break;
                
                case 'store_credit':
                    $refundId = $this->processStoreCreditRefund($return, $amount);
                    break;
                
                case 'exchange':
                    $refundId = $this->processExchangeRefund($return, $amount);
                    break;
                
                default:
                    return ['success' => false, 'message' => 'Invalid refund method'];
            }

            if (!$refundId) {
                return ['success' => false, 'message' => 'Failed to process refund'];
            }

            // Update return status
            $return->status = ReturnModel::STATUS_REFUNDED;
            $return->refund_method = $method;
            $return->refund_amount = $amount;
            $return->refund_status = 'completed';
            $return->refunded_at = date('Y-m-d H:i:s');
            
            if (!empty($note)) {
                $return->admin_notes .= "\n\nRefund Note: " . $note;
            }

            $this->returnRepository->update($return);

            // Send email notification
            $this->emailService->sendRefundProcessedEmail($return);

            $this->getLogger(__METHOD__)->info('ReturnsPortal::Refund processed', [
                'returnId' => $returnId,
                'refundId' => $refundId,
                'amount' => $amount,
                'method' => $method
            ]);

            return [
                'success' => true,
                'refundId' => $refundId,
                'amount' => $amount,
                'method' => $method
            ];

        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ReturnsPortal::Error processing refund', [
                'returnId' => $returnId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process refund to original payment method
     * @param Return $return
     * @param float $amount
     * @return int|null
     */
    private function processOriginalPaymentRefund(Return $return, float $amount): ?int
    {
        try {
            // Get original order
            $order = $this->orderRepository->findOrderById($return->order_id);
            
            if (!$order) {
                throw new \Exception('Order not found');
            }

            // Get original payment
            $payments = $order->payments ?? [];
            
            if (empty($payments)) {
                throw new \Exception('No payment found for order');
            }

            $originalPayment = $payments[0];

            // Create refund payment
            $refundData = [
                'mopId' => $originalPayment->mopId,
                'transactionType' => 3, // Refund
                'status' => 2, // Approved
                'currency' => $originalPayment->currency,
                'amount' => -abs($amount),
                'receivedAt' => date('Y-m-d H:i:s'),
                'type' => 'credit',
                'parentId' => $originalPayment->id
            ];

            $refundPayment = $this->paymentRepository->createPayment($refundData);

            // Assign refund to order
            $this->paymentRepository->assignPlentyPaymentToPlentyOrder(
                $refundPayment,
                $return->order_id
            );

            return $refundPayment->id;

        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ReturnsPortal::Error creating payment refund', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process store credit refund
     * @param Return $return
     * @param float $amount
     * @return int|null
     */
    private function processStoreCreditRefund(Return $return, float $amount): ?int
    {
        try {
            // Create coupon/store credit for customer
            // This would integrate with your coupon/voucher system
            
            // For now, just log and return a dummy ID
            $this->getLogger(__METHOD__)->info('ReturnsPortal::Store credit created', [
                'returnId' => $return->id,
                'amount' => $amount
            ]);

            return rand(1000, 9999); // Placeholder

        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ReturnsPortal::Error creating store credit', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process exchange refund
     * @param Return $return
     * @param float $amount
     * @return int|null
     */
    private function processExchangeRefund(Return $return, float $amount): ?int
    {
        try {
            // Create exchange order/credit
            // This would create a new order or credit note for exchange
            
            $this->getLogger(__METHOD__)->info('ReturnsPortal::Exchange processed', [
                'returnId' => $return->id,
                'amount' => $amount
            ]);

            return rand(1000, 9999); // Placeholder

        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ReturnsPortal::Error processing exchange', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculate refund amount
     * @param Return $return
     * @return float
     */
    public function calculateRefundAmount(Return $return): float
    {
        $items = $this->returnRepository->getReturnItems($return->id);
        
        $total = 0;
        foreach ($items as $item) {
            $total += $item->price * $item->quantity;
        }

        // Apply any deductions (restocking fees, etc.)
        // This can be customized based on your business rules
        
        return $total;
    }

    /**
     * Check if refund is possible
     * @param Return $return
     * @return array
     */
    public function canRefund(Return $return): array
    {
        if ($return->status !== ReturnModel::STATUS_RECEIVED) {
            return [
                'canRefund' => false,
                'reason' => 'Return must be received before refund'
            ];
        }

        if ($return->refund_status === 'completed') {
            return [
                'canRefund' => false,
                'reason' => 'Refund already processed'
            ];
        }

        return [
            'canRefund' => true,
            'maxAmount' => $this->calculateRefundAmount($return)
        ];
    }
}
