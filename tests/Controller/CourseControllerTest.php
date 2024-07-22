<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;

class CourseControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [
            CourseFixtures::class,
        ];
    }

    public function testCourseIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/api/v1/courses/');

        $this->assertResponseIsSuccessful();
    }
}
