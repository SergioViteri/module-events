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

class Data extends AbstractHelper
{
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
}
