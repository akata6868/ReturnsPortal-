<?php

namespace ReturnsPortal\Services;

use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\ConfigRepository;
use ReturnsPortal\Repositories\ReturnRepository;

/**
 * Class ReturnValidationService
 * @package ReturnsPortal\Services
 */
class ReturnValidationService
{
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * @var ConfigRepository
     */
    private $config;

    /**
     * @var ReturnRepository
     */
    private $returnRepository;

    /**
     * ReturnValidationService constructor.
     */
    public function __construct(
        OrderRepositoryContract $orderRepository,
        ConfigRepository $config,
        ReturnRepository $returnRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->returnRepository = $returnRepository;
    }

    /**
     * Validate if order is eligible for return
     * @param Order $order
     * @return array
     */
    public function validateOrderForReturn(Order $order): array
    {
        // Check if order exists
        if (!$order) {
            return [
                'eligible' => false,
                'message' => 'Order not found'
            ];
        }

        // Check order status
        if (!$this->isOrderCompleted($order)) {
            return [
                'eligible' => false,
                'message' => 'Order must be completed before return'
            ];
        }

        // Check return period
        $returnPeriodDays = $this->config->get('ReturnsPortal.autoApprovalDays', 14);
        $orderDate = new \DateTime($order->createdAt);
        $today = new \DateTime();
        $diff = $today->diff($orderDate);
        
        if ($diff->days > $returnPeriodDays) {
            return [
                'eligible' => false,
                'message' => 'Return period has expired (max ' . $returnPeriodDays . ' days)'
            ];
        }

        // Check if already returned
        $existingReturns = $this->returnRepository->findByContactId($order->contactReceiver->id ?? 0);
        foreach ($existingReturns as $existingReturn) {
            if ($existingReturn->order_id == $order->id && 
                !in_array($existingReturn->status, ['rejected', 'cancelled'])) {
                return [
                    'eligible' => false,
                    'message' => 'A return request already exists for this order'
                ];
            }
        }

        $deadline = clone $orderDate;
        $deadline->add(new \DateInterval('P' . $returnPeriodDays . 'D'));

        return [
            'eligible' => true,
            'message' => 'Order is eligible for return',
            'deadline' => $deadline->format('Y-m-d'),
            'daysLeft' => $returnPeriodDays - $diff->days
        ];
    }

    /**
     * Validate return request data
     * @param array $data
     * @return array
     */
    public function validateReturnRequest(array $data): array
    {
        $errors = [];

        // Required fields
        if (empty($data['order_id'])) {
            $errors['order_id'] = 'Order ID is required';
        }

        if (empty($data['customer_email'])) {
            $errors['customer_email'] = 'Email is required';
        } elseif (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['customer_email'] = 'Invalid email format';
        }

        if (empty($data['customer_name'])) {
            $errors['customer_name'] = 'Name is required';
        }

        if (empty($data['return_reason'])) {
            $errors['return_reason'] = 'Return reason is required';
        }

        // Validate items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors['items'] = 'At least one item must be selected';
        } else {
            $hasSelectedItem = false;
            foreach ($data['items'] as $index => $item) {
                if (!empty($item['selected'])) {
                    $hasSelectedItem = true;
                    
                    if (empty($item['quantity']) || $item['quantity'] < 1) {
                        $errors['items'][$index]['quantity'] = 'Invalid quantity';
                    }
                    
                    if (empty($item['reason'])) {
                        $errors['items'][$index]['reason'] = 'Return reason is required for each item';
                    }
                }
            }
            
            if (!$hasSelectedItem) {
                $errors['items'] = 'No items selected for return';
            }
        }

        // Validate order exists and is eligible
        if (!empty($data['order_id'])) {
            try {
                $order = $this->orderRepository->findOrderById($data['order_id']);
                $validation = $this->validateOrderForReturn($order);
                
                if (!$validation['eligible']) {
                    $errors['order'] = $validation['message'];
                }
            } catch (\Exception $e) {
                $errors['order'] = 'Order not found';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Validation passed' : 'Validation failed'
        ];
    }

    /**
     * Validate if item can be returned
     * @param $orderItem
     * @return bool
     */
    public function canReturnItem($orderItem): bool
    {
        // Check if item is returnable (not a service, digital product, etc.)
        if ($orderItem->typeId !== 1) { // 1 = variation item
            return false;
        }

        // Add more business rules here
        // e.g., custom products, personalized items, etc.

        return true;
    }

    /**
     * Check if order is completed
     * @param Order $order
     * @return bool
     */
    private function isOrderCompleted(Order $order): bool
    {
        // Check if order is in a completed status
        // Status 7 = Outgoing items booked
        // Status 7.4 = Shipped
        // You may need to adjust these based on your workflow
        return in_array($order->statusId, [7, 7.4, 8, 9]);
    }

    /**
     * Validate image upload
     * @param $file
     * @return array
     */
    public function validateImage($file): array
    {
        $errors = [];

        // Check file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed (5MB)';
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get maximum return period in days
     * @return int
     */
    public function getReturnPeriodDays(): int
    {
        return (int)$this->config->get('ReturnsPortal.autoApprovalDays', 14);
    }

    /**
     * Check if photos are required
     * @return bool
     */
    public function arePhotosRequired(): bool
    {
        return (bool)$this->config->get('ReturnsPortal.requirePhotos', false);
    }
}
