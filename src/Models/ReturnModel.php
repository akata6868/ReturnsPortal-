<?php

namespace ReturnsPortal\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * Class ReturnModel
 * @package ReturnsPortal\Models
 * 
 * @property int $id
 * @property string $return_number
 * @property int $order_id
 * @property int $contact_id
 * @property string $status
 * @property string $customer_email
 * @property string $customer_name
 * @property string $customer_phone
 * @property float $total_amount
 * @property string $return_reason
 * @property string $customer_notes
 * @property string $admin_notes
 * @property string $rejection_reason
 * @property string $refund_method
 * @property float $refund_amount
 * @property string $refund_status
 * @property string $tracking_number
 * @property string $shipping_carrier
 * @property \Carbon\Carbon $approved_at
 * @property \Carbon\Carbon $received_at
 * @property \Carbon\Carbon $refunded_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ReturnModel extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_RECEIVED = 'received';
    const STATUS_INSPECTING = 'inspecting';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $return_number = '';

    /**
     * @var int
     */
    public $order_id = 0;

    /**
     * @var int
     */
    public $contact_id = 0;

    /**
     * @var string
     */
    public $status = self::STATUS_PENDING;

    /**
     * @var string
     */
    public $customer_email = '';

    /**
     * @var string
     */
    public $customer_name = '';

    /**
     * @var string
     */
    public $customer_phone = '';

    /**
     * @var float
     */
    public $total_amount = 0.0;

    /**
     * @var string
     */
    public $return_reason = '';

    /**
     * @var string
     */
    public $customer_notes = '';

    /**
     * @var string
     */
    public $admin_notes = '';

    /**
     * @var string
     */
    public $rejection_reason = '';

    /**
     * @var string
     */
    public $refund_method = '';

    /**
     * @var float
     */
    public $refund_amount = 0.0;

    /**
     * @var string
     */
    public $refund_status = '';

    /**
     * @var string
     */
    public $tracking_number = '';

    /**
     * @var string
     */
    public $shipping_carrier = '';

    /**
     * @var string
     */
    public $approved_at = '';

    /**
     * @var string
     */
    public $received_at = '';

    /**
     * @var string
     */
    public $refunded_at = '';

    /**
     * @var string
     */
    public $created_at = '';

    /**
     * @var string
     */
    public $updated_at = '';

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return 'ReturnsPortal::returns';
    }

    /**
     * Get all available statuses
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_SHIPPED,
            self::STATUS_RECEIVED,
            self::STATUS_INSPECTING,
            self::STATUS_REFUNDED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        ];
    }

    /**
     * Check if return can be cancelled
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_APPROVED
        ]);
    }

    /**
     * Check if return can be refunded
     * @return bool
     */
    public function canBeRefunded(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    /**
     * Check if return is completed
     * @return bool
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REFUNDED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED
        ]);
    }
}
