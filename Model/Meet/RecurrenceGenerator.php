<?php
/**
 * Zacatrus Events Recurrence Generator
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\Meet;

use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Api\Data\MeetInterfaceFactory;
use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Model\ResourceModel\Meet\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\CouldNotSaveException;

class RecurrenceGenerator
{
    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var MeetInterfaceFactory
     */
    protected $meetFactory;

    /**
     * @var CollectionFactory
     */
    protected $meetCollectionFactory;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * Default recurrence period in months
     */
    const DEFAULT_RECURRENCE_MONTHS = 6;

    /**
     * Days between quincenal meets
     */
    const QUINCENAL_DAYS = 15;

    /**
     * @param MeetRepositoryInterface $meetRepository
     * @param MeetInterfaceFactory $meetFactory
     * @param CollectionFactory $meetCollectionFactory
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        MeetRepositoryInterface $meetRepository,
        MeetInterfaceFactory $meetFactory,
        CollectionFactory $meetCollectionFactory,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->meetRepository = $meetRepository;
        $this->meetFactory = $meetFactory;
        $this->meetCollectionFactory = $meetCollectionFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Generate recurring meets for a parent meet
     *
     * @param MeetInterface $parentMeet
     * @return void
     * @throws CouldNotSaveException
     */
    public function generateRecurringMeets(MeetInterface $parentMeet): void
    {
        if ($parentMeet->getRecurrenceType() !== MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
            return;
        }

        $startDate = new \DateTime($parentMeet->getStartDate());
        $endDate = clone $startDate;
        $endDate->modify('+' . self::DEFAULT_RECURRENCE_MONTHS . ' months');

        $currentDate = clone $startDate;
        $currentDate->modify('+' . self::QUINCENAL_DAYS . ' days');

        while ($currentDate <= $endDate) {
            // Check if meet already exists for this date and location
            $collection = $this->meetCollectionFactory->create();
            $collection->addFieldToFilter('location_id', $parentMeet->getLocationId())
                ->addFieldToFilter('start_date', $currentDate->format('Y-m-d H:i:s'))
                ->addFieldToFilter('name', $parentMeet->getName());
            
            if ($collection->getSize() > 0) {
                $currentDate->modify('+' . self::QUINCENAL_DAYS . ' days');
                continue;
            }
            
            // Create new meet
            $newMeet = $this->meetFactory->create();
            $newMeet->setName($parentMeet->getName())
                ->setLocationId($parentMeet->getLocationId())
                ->setMeetType($parentMeet->getMeetType())
                ->setStartDate($currentDate->format('Y-m-d H:i:s'))
                ->setDurationMinutes($parentMeet->getDurationMinutes())
                ->setMaxSlots($parentMeet->getMaxSlots())
                ->setDescription($parentMeet->getDescription())
                ->setRecurrenceType(MeetInterface::RECURRENCE_TYPE_NONE) // Child meets are not recurrent
                ->setIsActive($parentMeet->getIsActive());
            
            $this->meetRepository->save($newMeet);

            $currentDate->modify('+' . self::QUINCENAL_DAYS . ' days');
        }
    }

    /**
     * Generate recurring meets after saving a parent meet
     *
     * @param MeetInterface $meet
     * @return void
     */
    public function processAfterSave(MeetInterface $meet): void
    {
        if ($meet->getRecurrenceType() === MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
            try {
                $this->generateRecurringMeets($meet);
            } catch (\Exception $e) {
                // Log error but don't fail the save
                // In production, use proper logging
            }
        }
    }
}

