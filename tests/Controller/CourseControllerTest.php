<?php

namespace App\Tests\Controller;

use App\Config\CourseType;
use App\DataFixtures\CourseFixtures;
use App\DataFixtures\UserFixtures;
use App\Tests\AbstractTest;
use App\Entity\Course;

class CourseControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [
            UserFixtures::class,
            CourseFixtures::class,
        ];
    }

    public function testCourseIndex(): void
    {
        $client = static::getClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $client->jsonRequest('GET', '/api/v1/courses/');
        $this->assertResponseIsSuccessful();

        $coursesResponse = json_decode($client->getResponse()->getContent(), true);
        $courses = $entityManager->getRepository(Course::class)->findAll();

        for ($i = 0; $i < count($courses); $i++) {
            $this->assertEquals(
                $courses[$i]->getCharsCode(),
                $coursesResponse[$i]['code'],
            );
            $this->assertEquals(
                $courses[$i]->getType(),
                CourseType::stringToType($coursesResponse[$i]['type'])
            );
            if ($courses[$i]->getType() !== CourseType::FREE) {
                $this->assertEquals(
                    $courses[$i]->getPrice(),
                    $coursesResponse[$i]['price']
                );
            }
        }

    }

    public function testCourseShow(): void
    {
        $client = static::getClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $courses = $entityManager->getRepository(Course::class)->findAll();
        
        foreach ($courses as $course) {
            $client->jsonRequest('GET', '/api/v1/courses/' . $course->getCharsCode());
            $this->assertResponseIsSuccessful();
            $courseResponse = json_decode($client->getResponse()->getContent(), true);

            $this->assertEquals(
                $course->getType(),
                CourseType::stringToType($courseResponse['type'])
            );

            if ($course->getType() !== CourseType::FREE) {
                $this->assertEquals(
                    $course->getPrice(),
                    $courseResponse['price']
                );
            }
        }

        $client->request('GET', '/api/v1/courses/programming');
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals(
            'Курс не найден',
            json_decode($client->getResponse()->getContent(), true)['message']
        );
    }

    public function testCoursePay(): void
    {
        $client = static::getClient();
                
        // Авторизация
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => 'petrov@email.ru',
            'password' => 'qwer1234',
        ]);
        $this->assertResponseIsSuccessful();
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
        $client->jsonRequest('POST', '/api/v1/courses/math/pay');
        $this->assertResponseIsSuccessful();

        $this->assertEquals(
            'Курс куплен',
            json_decode($client->getResponse()->getContent(), true)['message']
        );

        $this->assertEquals(
            'math',
            json_decode($client->getResponse()->getContent(), true)['course_code']
        );
        
        // Проверка при не достаточном количестве средств на счету
        $client->jsonRequest('POST', '/api/v1/courses/physics/pay');
        $this->assertResponseStatusCodeSame(406);
        $this->assertEquals(
            'На вашем счету не достаточно средств',
            json_decode($client->getResponse()->getContent(), true)['message'],
        );

        // При передачи не существуещего курса
        $client->request('POST', '/api/v1/courses/programming/pay');
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals(
            'Курс не найден',
            json_decode($client->getResponse()->getContent(), true)['message']
        );
    }   
}
