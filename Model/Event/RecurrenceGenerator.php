<?php
/**
 * Zacatrus Events Recurrence Generator
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Model\Event;

use Zacatrus\Events\Api\Data\EventInterface;
use Zacatrus\Events\Api\Data\EventInterfaceFactory;
use Zacatrus\Events\Api\EventRepositoryInterface;
use Zacatrus\Events\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;

class RecurrenceGenerator
{
    /**
     * @var EventRepositoryInterface
     */
    protected $eventRepository;

    /**
     * @var EventInterfaceFactory
     */
    protected $eventFactory;

    /**
     * @var CollectionFactory
     */
    protected $eventCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Default recurrence period in months
     */
    const DEFAULT_RECURRENCE_MONTHS = 6;

    /**
     * Days between quincenal events
     */
    const QUINCENAL_DAYS = 15;

    /**
     * @param EventRepositoryInterface $eventRepository
     * @param EventInterfaceFactory $eventFactory
     * @param CollectionFactory $eventCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        EventRepositoryInterface $eventRepository,
        EventInterfaceFactory $eventFactory,
        CollectionFactory $eventCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->eventRepository = $eventRepository;
        $this->eventFactory = $eventFactory;
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Generate recurring events for a parent event
     *
     * @param EventInterface $parentEvent
     * @return void
     * @throws CouldNotSaveException
     */
    public function generateRecurringEvents(EventInterface $parentEvent): void
    {
        if ($parentEvent->getRecurrenceType() !== EventInterface::RECURRENCE_TYPE_QUINCENAL) {
            return;
        }

        $startDate = new \DateTime($parentEvent->getStartDate());
        $endDate = clone $startDate;
        $endDate->modify('+' . self::DEFAULT_RECURRENCE_MONTHS . ' months');

        $currentDate = clone $startDate;
        $currentDate->modify('+' . self::QUINCENAL_DAYS . ' days');

        while ($currentDate <= $endDate) {
            // Check if event already exists for this date and store
            $collection = $this->eventCollectionFactory->create();
            $collection->addFieldToFilter('store_id', $parentEvent->getStoreId())
                ->addFieldToFilter('start_date', $currentDate->format('Y-m-d H:i:s'))
                ->addFieldToFilter('name', $parentEvent->getName());
            
            if ($collection->getSize() > 0) {
                $currentDate->modify('+' . self::QUINCENAL_DAYS . ' days');
                continue;
            }
            
            // Create new event
            $newEvent = $this->eventFactory->create();
            $newEvent->setName($parentEvent->getName())
                ->setStoreId($parentEvent->getStoreId())
                ->setEventType($parentEvent->getEventType())
                ->setStartDate($currentDate->format('Y-m-d H:i:s'))
                ->setDurationMinutes($parentEvent->getDurationMinutes())
                ->setMaxSlots($parentEvent->getMaxSlots())
                ->setDescription($parentEvent->getDescription())
                ->setRecurrenceType(EventInterface::RECURRENCE_TYPE_NONE) // Child events are not recurrent
                ->setIsActive($parentEvent->getIsActive());
            
            $this->eventRepository->save($newEvent);

            $currentDate->modify('+' . self::QUINCENAL_DAYS . ' days');
        }
    }

    /**
     * Generate recurring events after saving a parent event
     *
     * @param EventInterface $event
     * @return void
     */
    public function processAfterSave(EventInterface $event): void
    {
        if ($event->getRecurrenceType() === EventInterface::RECURRENCE_TYPE_QUINCENAL) {
            try {
                $this->generateRecurringEvents($event);
            } catch (\Exception $e) {
                // Log error but don't fail the save
                // In production, use proper logging
            }
        }
    }
}

