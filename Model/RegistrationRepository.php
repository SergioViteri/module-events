<?php
/**
 * Zacatrus Events Registration Repository
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Api\Data\RegistrationInterface;
use Zaca\Events\Api\Data\RegistrationInterfaceFactory;
use Zaca\Events\Model\ResourceModel\Registration as RegistrationResourceModel;
use Zaca\Events\Model\ResourceModel\Registration\CollectionFactory as RegistrationCollectionFactory;
use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Helper\Email as EmailHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DB\TransactionFactory;
use Psr\Log\LoggerInterface;

class RegistrationRepository implements RegistrationRepositoryInterface
{
    /**
     * @var RegistrationResourceModel
     */
    protected $resource;

    /**
     * @var RegistrationInterfaceFactory
     */
    protected $registrationFactory;

    /**
     * @var RegistrationCollectionFactory
     */
    protected $registrationCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var EmailHelper
     */
    protected $emailHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RegistrationResourceModel $resource
     * @param RegistrationInterfaceFactory $registrationFactory
     * @param RegistrationCollectionFactory $registrationCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param MeetRepositoryInterface $meetRepository
     * @param TransactionFactory $transactionFactory
     * @param EmailHelper $emailHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        RegistrationResourceModel $resource,
        RegistrationInterfaceFactory $registrationFactory,
        RegistrationCollectionFactory $registrationCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        MeetRepositoryInterface $meetRepository,
        TransactionFactory $transactionFactory,
        EmailHelper $emailHelper,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->registrationFactory = $registrationFactory;
        $this->registrationCollectionFactory = $registrationCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->meetRepository = $meetRepository;
        $this->transactionFactory = $transactionFactory;
        $this->emailHelper = $emailHelper;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function save(RegistrationInterface $registration): RegistrationInterface
    {
        try {
            $this->resource->save($registration);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the registration: %1', $exception->getMessage()));
        }
        return $registration;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $registrationId): RegistrationInterface
    {
        $registration = $this->registrationFactory->create();
        $this->resource->load($registration, $registrationId);
        if (!$registration->getRegistrationId()) {
            throw new NoSuchEntityException(__('Registration with id "%1" does not exist.', $registrationId));
        }
        return $registration;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->registrationCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(RegistrationInterface $registration): bool
    {
        try {
            $meetId = $registration->getMeetId();
            $this->resource->delete($registration);

            $meet = $this->meetRepository->getById($meetId);
            $maxSlots = (int) $meet->getMaxSlots();

            // Promote from waitlist until confirmed slots are filled or waitlist is empty
            while ($this->getConfirmedAttendeeCountForMeet($meetId) < $maxSlots) {
                $promoted = $this->promoteOneFromWaitlist($meetId);
                if (!$promoted) {
                    break;
                }
            }
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the registration: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $registrationId): bool
    {
        return $this->delete($this->getById($registrationId));
    }

    /**
     * @inheritdoc
     */
    public function getConfirmedAttendeeCountForMeet(int $meetId): int
    {
        return $this->resource->getConfirmedAttendeeSum($meetId);
    }

    /**
     * @inheritdoc
     */
    public function registerCustomer(int $customerId, int $meetId, ?string $phoneNumber = null, ?int $attendeeCount = null): RegistrationInterface
    {
        // Check if already registered
        $collection = $this->registrationCollectionFactory->create();
        $collection->addFieldToFilter('meet_id', $meetId)
            ->addFieldToFilter('customer_id', $customerId);
        
        if ($collection->getSize() > 0) {
            return $collection->getFirstItem();
        }

        // Get meet
        $meet = $this->meetRepository->getById($meetId);
        
        // Validate meet is active
        if (!$meet->getIsActive()) {
            throw new LocalizedException(__('This meet is not active.'));
        }

        $maxPerRegistration = (int) $meet->getMaxAttendeesPerRegistration();
        if ($maxPerRegistration < 1) {
            $maxPerRegistration = 1;
        }
        $attendeeCount = $attendeeCount !== null ? (int) $attendeeCount : 1;
        if ($attendeeCount < 1 || $attendeeCount > $maxPerRegistration) {
            $attendeeCount = max(1, min($attendeeCount, $maxPerRegistration));
        }

        $occurrenceEnd = $this->getOccurrenceEndDateTime($meet);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if ($occurrenceEnd === null || $now >= $occurrenceEnd) {
            throw new LocalizedException(__('Registration for this event has closed.'));
        }

        // Check available slots (sum of attendee_count for confirmed registrations)
        $confirmedSum = $this->getConfirmedAttendeeCountForMeet($meetId);
        $status = RegistrationInterface::STATUS_CONFIRMED;
        if ($confirmedSum + $attendeeCount > $meet->getMaxSlots()) {
            $status = RegistrationInterface::STATUS_WAITLIST;
        }

        // Create registration
        $registration = $this->registrationFactory->create();
        $registration->setMeetId($meetId)
            ->setCustomerId($customerId)
            ->setStatus($status)
            ->setRegistrationDate($now->format('Y-m-d H:i:s'))
            ->setAttendeeCount($attendeeCount);
        
        // Set phone number if provided
        if ($phoneNumber !== null && $phoneNumber !== '') {
            $registration->setPhoneNumber($phoneNumber);
        }

        $savedRegistration = $this->save($registration);

        // Send registration email (frontend initiated)
        try {
            $this->emailHelper->sendRegistrationEmail($savedRegistration, false);
        } catch (\Exception $e) {
            // Log error but don't fail registration
            $this->logger->error('[RegistrationRepository] Error sending registration email: ' . $e->getMessage());
            $this->logger->error('[RegistrationRepository] Stack trace: ' . $e->getTraceAsString());
        }

        return $savedRegistration;
    }

    /**
     * @inheritdoc
     */
    public function unregisterCustomer(int $customerId, int $meetId): bool
    {
        $collection = $this->registrationCollectionFactory->create();
        $collection->addFieldToFilter('meet_id', $meetId)
            ->addFieldToFilter('customer_id', $customerId);
        
        if ($collection->getSize() === 0) {
            throw new NoSuchEntityException(__('You are not registered for this meet.'));
        }

        $registration = $collection->getFirstItem();
        
        // Get meet and customer data before deletion for email
        $meet = $this->meetRepository->getById($meetId);
        $customerEmail = '';
        $customerName = '';
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerEmail = $customer->getEmail();
            $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
        } catch (\Exception $e) {
            $this->logger->warning('[RegistrationRepository] Could not load customer for unregistration email: ' . $e->getMessage());
        }

        $result = $this->delete($registration);

        // Send unregistration email (frontend initiated)
        if ($result && $customerEmail && $customerName) {
            try {
                $this->emailHelper->sendUnregistrationEmail($registration, $meet, $customerEmail, $customerName, false);
            } catch (\Exception $e) {
                // Log error but don't fail unregistration
                $this->logger->error('[RegistrationRepository] Error sending unregistration email: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Promote one waitlist registration to confirmed. Called in a loop after delete until slots are filled.
     *
     * @param int $meetId
     * @return bool true if someone was promoted, false if waitlist empty
     */
    protected function promoteOneFromWaitlist(int $meetId): bool
    {
        $waitlistCollection = $this->registrationCollectionFactory->create();
        $waitlistCollection->addFieldToFilter('meet_id', $meetId)
            ->addFieldToFilter('status', RegistrationInterface::STATUS_WAITLIST)
            ->setOrder('created_at', 'ASC')
            ->setPageSize(1);

        if ($waitlistCollection->getSize() === 0) {
            return false;
        }

        $waitlistRegistration = $waitlistCollection->getFirstItem();
        $waitlistRegistration->setStatus(RegistrationInterface::STATUS_CONFIRMED);
        $this->save($waitlistRegistration);

        try {
            $this->emailHelper->sendWaitlistPromotionEmail($waitlistRegistration);
        } catch (\Exception $e) {
            $this->logger->error('[RegistrationRepository] Error sending waitlist promotion email: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Get most recent phone number from customer's registrations
     *
     * @param int $customerId
     * @param int|null $excludeMeetId
     * @return string|null
     */
    public function getMostRecentPhoneNumber(int $customerId, ?int $excludeMeetId = null): ?string
    {
        try {
            $collection = $this->registrationCollectionFactory->create();
            $collection->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('phone_number', ['notnull' => true])
                ->addFieldToFilter('phone_number', ['neq' => '']);
            
            if ($excludeMeetId !== null) {
                $collection->addFieldToFilter('meet_id', ['neq' => $excludeMeetId]);
            }
            
            $collection->setOrder('created_at', 'DESC')
                ->setPageSize(1);
            
            if ($collection->getSize() > 0) {
                $registration = $collection->getFirstItem();
                return $registration->getPhoneNumber();
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('[RegistrationRepository] Error getting most recent phone number: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get end datetime of the current/next occurrence (start + duration) in UTC.
     * Returns null if no valid occurrence (e.g. recurring with next occurrence after end_date).
     *
     * @param MeetInterface $meet
     * @return \DateTime|null
     */
    private function getOccurrenceEndDateTime(MeetInterface $meet): ?\DateTime
    {
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        $durationMinutes = (int) $meet->getDurationMinutes();
        $recurrenceType = $meet->getRecurrenceType();

        if ($recurrenceType === MeetInterface::RECURRENCE_TYPE_NONE) {
            $start = new \DateTime($meet->getStartDate(), new \DateTimeZone('UTC'));
            $end = clone $start;
            $end->modify('+' . $durationMinutes . ' minutes');
            return $end;
        }

        if ($recurrenceType === MeetInterface::RECURRENCE_TYPE_QUINCENAL ||
            $recurrenceType === MeetInterface::RECURRENCE_TYPE_SEMANAL) {
            $intervalDays = $recurrenceType === MeetInterface::RECURRENCE_TYPE_QUINCENAL ? 15 : 7;
            $start = new \DateTime($meet->getStartDate(), new \DateTimeZone('UTC'));
            $nextStart = clone $start;
            while ($nextStart <= $nowUtc) {
                $nextStart->modify('+' . $intervalDays . ' days');
            }
            $endDate = $meet->getEndDate();
            if ($endDate !== null) {
                $endLimit = new \DateTime($endDate, new \DateTimeZone('UTC'));
                if ($nextStart > $endLimit) {
                    return null;
                }
            }
            $occurrenceEnd = clone $nextStart;
            $occurrenceEnd->modify('+' . $durationMinutes . ' minutes');
            return $occurrenceEnd;
        }

        return null;
    }
}

