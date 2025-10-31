<?php

namespace ReturnsPortal\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use ReturnsPortal\Models\ReturnModel;
use ReturnsPortal\Models\ReturnItem;

/**
 * Class ReturnRepository
 * @package ReturnsPortal\Repositories
 */
class ReturnRepository
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * ReturnRepository constructor.
     * @param DataBase $database
     */
    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Find return by ID
     * @param int $id
     * @return ReturnModel|null
     */
    public function findById(int $id): ?ReturnModel
    {
        return $this->database->find(ReturnModel::class, $id);
    }

    /**
     * Find return by ID with all relations
     * @param int $id
     * @return ReturnModel|null
     */
    public function findByIdWithRelations(int $id): ?ReturnModel
    {
        $returnRecord = $this->findById($id);
        
        if ($returnRecord) {
            // Load return items
            $returnRecord->items = $this->getReturnItems($id);
        }
        
        return $returnRecord;
    }

    /**
     * Find returns by contact ID
     * @param int $contactId
     * @return array
     */
    public function findByContactId(int $contactId): array
    {
        return $this->database->query(ReturnModel::class)
            ->where('contact_id', '=', $contactId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find return by return number
     * @param string $returnNumber
     * @return ReturnModel|null
     */
    public function findByReturnNumber(string $returnNumber): ?ReturnModel
    {
        $results = $this->database->query(ReturnModel::class)
            ->where('return_number', '=', $returnNumber)
            ->get();
        
        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * Search returns with filters
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function search(array $filters, int $page = 1, int $perPage = 50): array
    {
        $query = $this->database->query(ReturnModel::class);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', '=', $filters['status']);
        }

        if (!empty($filters['dateFrom'])) {
            $query->where('created_at', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('created_at', '<=', $filters['dateTo']);
        }

        if (!empty($filters['searchTerm'])) {
            $query->where(function($q) use ($filters) {
                $q->where('return_number', 'like', '%' . $filters['searchTerm'] . '%')
                  ->orWhere('customer_email', 'like', '%' . $filters['searchTerm'] . '%')
                  ->orWhere('customer_name', 'like', '%' . $filters['searchTerm'] . '%');
            });
        }

        // Count total
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $results = $query
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create a new return
     * @param array $data
     * @return ReturnModel
     */
    public function create(array $data): ReturnModel
    {
        $returnRecord = pluginApp(ReturnModel::class);
        
        $returnRecord->return_number = $this->generateReturnNumber();
        $returnRecord->order_id = $data['order_id'];
        $returnRecord->contact_id = $data['contact_id'] ?? 0;
        $returnRecord->customer_email = $data['customer_email'];
        $returnRecord->customer_name = $data['customer_name'];
        $returnRecord->customer_phone = $data['customer_phone'] ?? '';
        $returnRecord->return_reason = $data['return_reason'];
        $returnRecord->customer_notes = $data['customer_notes'] ?? '';
        $returnRecord->total_amount = $data['total_amount'] ?? 0;
        $returnRecord->status = ReturnModel::STATUS_PENDING;
        $returnRecord->created_at = date('Y-m-d H:i:s');
        $returnRecord->updated_at = date('Y-m-d H:i:s');

        return $this->database->save($returnRecord);
    }

    /**
     * Update return
     * @param ReturnModelModel $returnRecord
     * @return ReturnModel
     */
    public function update(ReturnModel $returnRecord): ReturnModel
    {
        $returnRecord->updated_at = date('Y-m-d H:i:s');
        return $this->database->save($returnRecord);
    }

    /**
     * Delete return
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $returnRecord = $this->findById($id);
        if ($returnRecord) {
            return $this->database->delete($returnRecord);
        }
        return false;
    }

    /**
     * Get return items
     * @param int $returnId
     * @return array
     */
    public function getReturnItems(int $returnId): array
    {
        return $this->database->query(ReturnItem::class)
            ->where('return_id', '=', $returnId)
            ->get();
    }

    /**
     * Create return item
     * @param array $data
     * @return ReturnModelItem
     */
    public function createReturnItem(array $data): ReturnModelItem
    {
        $item = pluginApp(ReturnItem::class);
        
        $item->return_id = $data['return_id'];
        $item->order_item_id = $data['order_item_id'];
        $item->item_variation_id = $data['item_variation_id'];
        $item->item_name = $data['item_name'];
        $item->sku = $data['sku'] ?? '';
        $item->quantity = $data['quantity'];
        $item->price = $data['price'];
        $item->reason = $data['reason'] ?? '';
        $item->notes = $data['notes'] ?? '';
        $item->condition = $data['condition'] ?? '';
        
        if (isset($data['images']) && is_array($data['images'])) {
            $item->setImagesArray($data['images']);
        }
        
        $item->created_at = date('Y-m-d H:i:s');
        $item->updated_at = date('Y-m-d H:i:s');

        return $this->database->save($item);
    }

    /**
     * Generate unique return number
     * @return string
     */
    private function generateReturnNumber(): string
    {
        $prefix = 'RET';
        $timestamp = time();
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . '-' . $timestamp . '-' . $random;
    }

    /**
     * Get statistics
     * @return array
     */
    public function getStatistics(): array
    {
        $totalReturns = $this->database->query(ReturnModel::class)->count();
        $pendingReturns = $this->database->query(ReturnModel::class)
            ->where('status', '=', ReturnModel::STATUS_PENDING)
            ->count();
        $approvedReturns = $this->database->query(ReturnModel::class)
            ->where('status', '=', ReturnModel::STATUS_APPROVED)
            ->count();
        $completedReturns = $this->database->query(ReturnModel::class)
            ->where('status', '=', ReturnModel::STATUS_COMPLETED)
            ->count();
        
        // Calculate total refund amount
        $returns = $this->database->query(ReturnModel::class)
            ->whereIn('status', [ReturnModel::STATUS_REFUNDED, ReturnModel::STATUS_COMPLETED])
            ->get();
        
        $totalRefunded = 0;
        foreach ($returns as $returnRecord) {
            $totalRefunded += $returnRecord->refund_amount;
        }

        return [
            'total' => $totalReturns,
            'pending' => $pendingReturns,
            'approved' => $approvedReturns,
            'completed' => $completedReturns,
            'totalRefunded' => $totalRefunded
        ];
    }
}
