<?php

namespace App\MessageHandler;

use App\Message\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EmailHandler
{
    public function __invoke(Email $email): void
    {
        dump($email);
    }
}