<?php

namespace App\Command;

use App\Message\Email;
use App\Service\Email\RentNotificationMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand('app:email:test')]
class TestEmailCommand extends Command
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private MailerInterface $mailer,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $log = new SymfonyStyle($input, $output);
        $this->messageBus->dispatch(new Email('привет'));
        return Command::SUCCESS;
    }
}