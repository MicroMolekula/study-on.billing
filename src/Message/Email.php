<?php

namespace App\Message;

class Email
{
    public function __construct(
        private string $content,
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): Email
    {
        $this->content = $content;
        return $this;
    }
}