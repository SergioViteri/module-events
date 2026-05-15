<?php

namespace Zaca\Events\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DayOfWeek implements OptionSourceInterface
{
    /**
     * Special non-ISO bucket: 8 = weekdays (Mon–Fri).
     * Stored as smallint in zaca_events_time_slot.day_of_week alongside ISO 1..7
     * and NULL (= all days). Resolved at runtime by AvailabilityService.
     */
    public const WEEKDAY = 8;

    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('All days')],
            ['value' => self::WEEKDAY, 'label' => __('Weekdays (Mon–Fri)')],
            ['value' => 1, 'label' => __('Monday')],
            ['value' => 2, 'label' => __('Tuesday')],
            ['value' => 3, 'label' => __('Wednesday')],
            ['value' => 4, 'label' => __('Thursday')],
            ['value' => 5, 'label' => __('Friday')],
            ['value' => 6, 'label' => __('Saturday')],
            ['value' => 7, 'label' => __('Sunday')],
        ];
    }

    /**
     * Map for grid column rendering.
     */
    public function toMap(): array
    {
        $map = [];
        foreach ($this->toOptionArray() as $row) {
            $map[(string) $row['value']] = (string) $row['label'];
        }
        return $map;
    }
}
