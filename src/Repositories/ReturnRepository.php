<?php

namespace ReturnsPortal\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use ReturnsPortal\Models\Return;
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
     * @return Return|null
     */
    public function findById(int $id): ?Return
    {
        return $this->database->find(Return::class, $id);
    }

    /**
     * Find return by ID with all relations
     * @param int $id
     * @return Return|null
     */
    public function findByIdWithRelations(int $id): ?Return
    {
        $return = $this->findById($id);
        
        if ($return) {
            // Load return items
            $return->items = $this->getReturnItems($id);
        }
        
        return $return;
    }

    /**
     * Find returns by contact ID
     * @param int $contactId
     * @return array
     */
    public function findByContactId(int $contactId): array
    {
        return $this->database->query(Return::class)
            ->where('contact_id', '=', $contactId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find return by return number
     * @param string $returnNumber
     * @return Return|null
     */
    public function findByReturnNumber(string $returnNumber): ?Return
    {
        $results = $this->database->query(Return::class)
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
        $query = $this->database->query(Return::class);

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
     * @return Return
     */
    public function create(array $data): Return
    {
        $return = pluginApp(Return::class);
        
        $return->return_number = $this->generateReturnNumber();
        $return->order_id = $data['order_id'];
        $return->contact_id = $data['contact_id'] ?? 0;
        $return->customer_email = $data['customer_email'];
        $return->customer_name = $data['customer_name'];
        $return->customer_phone = $data['customer_phone'] ?? '';
        $return->return_reason = $data['return_reason'];
        $return->customer_notes = $data['customer_notes'] ?? '';
        $return->total_amount = $data['total_amount'] ?? 0;
        $return->status = Return::STATUS_PENDING;
        $return->created_at = date('Y-m-d H:i:s');
        $return->updated_at = date('Y-m-d H:i:s');

        return $this->database->save($return);
    }

    /**
     * Update return
     * @param Return $return
     * @return Return
     */
    public function update(Return $return): Return
    {
        $return->updated_at = date('Y-m-d H:i:s');
        return $this->database->save($return);
    }

    /**
     * Delete return
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $return = $this->findById($id);
        if ($return) {
            return $this->database->delete($return);
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
     * @return ReturnItem
     */
    public function createReturnItem(array $data): ReturnItem
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
        $totalReturns = $this->database->query(Return::class)->count();
        $pendingReturns = $this->database->query(Return::class)
            ->where('status', '=', Return::STATUS_PENDING)
            ->count();
        $approvedReturns = $this->database->query(Return::class)
            ->where('status', '=', Return::STATUS_APPROVED)
            ->count();
        $completedReturns = $this->database->query(Return::class)
            ->where('status', '=', Return::STATUS_COMPLETED)
            ->count();
        
        // Calculate total refund amount
        $returns = $this->database->query(Return::class)
            ->whereIn('status', [Return::STATUS_REFUNDED, Return::STATUS_COMPLETED])
            ->get();
        
        $totalRefunded = 0;
        foreach ($returns as $return) {
            $totalRefunded += $return->refund_amount;
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
