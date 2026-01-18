<?php
/**
 * Zacatrus Events Slots Display Mode Source Model
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class SlotsDisplayMode implements ArrayInterface
{
    const MODE_AVAILABLE_TOTAL = 'available_total';
    const MODE_AVAILABLE = 'available';
    const MODE_NONE = 'none';

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::MODE_AVAILABLE_TOTAL, 'label' => __('%1 / %2', '<available>', '<total>')],
            ['value' => self::MODE_AVAILABLE, 'label' => __('%1', '<available>')],
            ['value' => self::MODE_NONE, 'label' => __('Nothing')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::MODE_AVAILABLE_TOTAL => __('%1 / %2', '<available>', '<total>'),
            self::MODE_AVAILABLE => __('%1', '<available>'),
            self::MODE_NONE => __('Nothing'),
        ];
    }
}
