<?php
/**
 * Zacatrus Events Send Reminders Console Command
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Console\Command;

use Zaca\Events\Cron\SendReminders;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendRemindersCommand extends Command
{
    /**
     * Dry run option
     */
    const OPTION_DRY_RUN = 'dry-run';

    /**
     * @var SendReminders
     */
    protected $sendReminders;

    /**
     * @param SendReminders $sendReminders
     * @param string|null $name
     */
    public function __construct(
        SendReminders $sendReminders,
        $name = null
    ) {
        parent::__construct($name);
        $this->sendReminders = $sendReminders;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('events:send-reminders')
            ->setDescription('Send reminder emails for upcoming events')
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Run without actually sending emails (simulation mode)'
            );
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Starting reminder email process...</info>');

        if ($input->getOption(self::OPTION_DRY_RUN)) {
            $output->writeln('<comment>DRY RUN MODE: No emails will be sent</comment>');
        }

        try {
            // Execute the same logic as the cron job
            $this->sendReminders->execute();

            $output->writeln('<info>Reminder email process completed successfully.</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}

