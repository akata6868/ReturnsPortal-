<?php

namespace ReturnsPortal\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * Class ReturnItem
 * @package ReturnsPortal\Models
 * 
 * @property int $id
 * @property int $return_id
 * @property int $order_item_id
 * @property int $item_variation_id
 * @property string $item_name
 * @property string $sku
 * @property int $quantity
 * @property float $price
 * @property string $reason
 * @property string $condition
 * @property string $notes
 * @property string $images
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ReturnItem extends Model
{
    const CONDITION_NEW = 'new';
    const CONDITION_USED_GOOD = 'used_good';
    const CONDITION_USED_FAIR = 'used_fair';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_DEFECTIVE = 'defective';

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $return_id = 0;

    /**
     * @var int
     */
    public $order_item_id = 0;

    /**
     * @var int
     */
    public $item_variation_id = 0;

    /**
     * @var string
     */
    public $item_name = '';

    /**
     * @var string
     */
    public $sku = '';

    /**
     * @var int
     */
    public $quantity = 1;

    /**
     * @var float
     */
    public $price = 0.0;

    /**
     * @var string
     */
    public $reason = '';

    /**
     * @var string
     */
    public $condition = '';

    /**
     * @var string
     */
    public $notes = '';

    /**
     * @var string
     */
    public $images = '';

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
        return 'camii_setting_return_items';
    }

    /**
     * Get all available conditions
     * @return array
     */
    public static function getConditions(): array
    {
        return [
            self::CONDITION_NEW,
            self::CONDITION_USED_GOOD,
            self::CONDITION_USED_FAIR,
            self::CONDITION_DAMAGED,
            self::CONDITION_DEFECTIVE
        ];
    }

    /**
     * Get images as array
     * @return array
     */
    public function getImagesArray(): array
    {
        if (empty($this->images)) {
            return [];
        }
        return json_decode($this->images, true) ?: [];
    }

    /**
     * Set images from array
     * @param array $images
     */
    public function setImagesArray(array $images): void
    {
        $this->images = json_encode($images);
    }
}
