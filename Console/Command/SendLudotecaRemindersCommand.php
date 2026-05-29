<?php

namespace Zaca\Events\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zaca\Events\Cron\SendLudotecaReminders;

class SendLudotecaRemindersCommand extends Command
{
    private SendLudotecaReminders $cron;

    public function __construct(SendLudotecaReminders $cron, $name = null)
    {
        parent::__construct($name);
        $this->cron = $cron;
    }

    protected function configure()
    {
        $this->setName('events:send-ludoteca-reminders')
            ->setDescription('Send reminder emails for upcoming ludoteca bookings');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Sending ludoteca booking reminders…</info>');
        try {
            $sent = $this->cron->execute();
            $output->writeln('<info>Done. ' . $sent . ' reminder(s) sent.</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
