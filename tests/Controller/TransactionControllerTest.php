<?php

namespace App\Tests\Controller;

use App\Tests\AbstractTest;

class TransactionControllerTest extends AbstractTest
{
    public function testSomething(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Hello World');
    }
}
