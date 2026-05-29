<?php
/**
 * Zacatrus Events Data Helper
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Zaca\Box\Helper\Club as ClubHelper;

class Data extends AbstractHelper
{
    private ClubHelper $clubHelper;

    public function __construct(
        Context $context,
        ClubHelper $clubHelper
    ) {
        parent::__construct($context);
        $this->clubHelper = $clubHelper;
    }

    /**
     * Get configured route path for events
     *
     * @param int|null $storeId Store ID (null for current store)
     * @return string Route path (default: 'events')
     */
    public function getRoutePath(?int $storeId = null): string
    {
        $path = $this->scopeConfig->getValue(
            'zaca_events/general/route_path',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        if (empty($path)) {
            return 'events';
        }
        
        // Sanitize: only alphanumeric, hyphens, underscores
        $path = preg_replace('/[^a-z0-9_-]/i', '', $path);
        $path = strtolower(trim($path));
        
        // Ensure it's not empty after sanitization
        if (empty($path)) {
            return 'events';
        }
        
        return $path;
    }

    /**
     * Get configured slots display mode
     *
     * @param int|null $storeId Store ID (null for current store)
     * @return string Display mode (default: 'available_total')
     */
    public function getSlotsDisplayMode(?int $storeId = null): string
    {
        $mode = $this->scopeConfig->getValue(
            'zaca_events/general/slots_display_mode',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        if (empty($mode)) {
            return 'available_total';
        }

        return $mode;
    }

    public function isLudotecaEnabled(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'zaca_events/ludoteca/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getLudotecaRoutePath(?int $storeId = null): string
    {
        $path = (string) $this->scopeConfig->getValue(
            'zaca_events/ludoteca/route_path',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $path = strtolower(trim(preg_replace('/[^a-z0-9_-]/i', '', $path) ?? ''));
        return $path !== '' ? $path : 'ludoteca';
    }

    /**
     * Maximum days in advance the given customer can book a ludoteca table.
     *
     * Includes today: a value of 2 means today and tomorrow are bookable.
     * Guest / non-Club → days_in_advance_default (default 2).
     * Club            → days_in_advance_club    (default 30).
     */
    public function getMaxAdvanceDays(?int $customerId, ?int $storeId = null): int
    {
        return $this->isClubMember($customerId)
            ? $this->getClubMaxAdvanceDays($storeId)
            : $this->getDefaultMaxAdvanceDays($storeId);
    }

    public function getDefaultMaxAdvanceDays(?int $storeId = null): int
    {
        return $this->readPositiveIntConfig(
            'zaca_events/ludoteca/days_in_advance_default',
            2,
            $storeId
        );
    }

    public function getClubMaxAdvanceDays(?int $storeId = null): int
    {
        return $this->readPositiveIntConfig(
            'zaca_events/ludoteca/days_in_advance_club',
            30,
            $storeId
        );
    }

    private function readPositiveIntConfig(string $path, int $default, ?int $storeId): int
    {
        $raw = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        if ($raw === null || $raw === '') {
            return $default;
        }
        $value = (int) $raw;
        return $value > 0 ? $value : $default;
    }

    public function getClubSignupUrl(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            'zaca_events/ludoteca/club_signup_url',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isClubMember(?int $customerId): bool
    {
        return $customerId !== null && $this->clubHelper->isCustomerClub($customerId);
    }

    /**
     * Days-before-booking that the reminder cron should target.
     *
     * @return int[]
     */
    public function getReminderDaysBefore(?int $storeId = null): array
    {
        $raw = (string) $this->scopeConfig->getValue(
            'zaca_events/ludoteca/reminder_days_before',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if (trim($raw) === '') {
            return [];
        }
        $days = [];
        foreach (explode(',', $raw) as $part) {
            $value = (int) trim($part);
            if ($value > 0) {
                $days[$value] = $value;
            }
        }
        return array_values($days);
    }

    /**
     * Build a public-facing ludoteca URL respecting the configured route path.
     * Pass an empty $tail for the landing, '<store-slug>' for a store page,
     * or 'reserve' / 'cancel' / 'qrcode' / etc. for reserved actions.
     */
    public function getLudotecaPublicUrl(string $tail = '', array $params = [], ?int $storeId = null): string
    {
        $base = '/' . $this->getLudotecaRoutePath($storeId);
        if ($tail !== '') {
            $base .= '/' . trim($tail, '/');
        }
        if (!empty($params)) {
            $base .= '?' . http_build_query($params);
        }
        return $base;
    }
}
