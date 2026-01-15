<?php
/**
 * Zacatrus Events Unsubscribe Block
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zaca\Events\Helper\Data as EventsHelper;

class Unsubscribe extends Template
{
    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

    /**
     * @param Context $context
     * @param EventsHelper $eventsHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        EventsHelper $eventsHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->eventsHelper = $eventsHelper;
    }

    /**
     * Get route path for events
     *
     * @return string
     */
    public function getRoutePath()
    {
        return $this->eventsHelper->getRoutePath();
    }
}
