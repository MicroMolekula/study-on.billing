<?php

namespace App\DataFixtures;

use App\Config\CourseType;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{

    private array $data = [
        [
            'chars_code' => 'english-language',
            'type' => CourseType::RENT,
            'price' => 1000.50,
        ],
        [
            'chars_code' => 'math',
            'type' => CourseType::BUY,
            'price' => 2000.50,
        ],
        [
            'chars_code' => 'chinesse-language',
            'type' => CourseType::RENT,
            'price' => 1500.30,
        ],
        [
            'chars_code' => 'history-of-russia',
            'type' => CourseType::FREE,
            'price' => null,
        ],
        [
            'chars_code' => 'physics',
            'type' => CourseType::BUY,
            'price' => 1900.20,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $dataCourse) {
            $course = new Course();
            $course->setCharsCode($dataCourse['chars_code'])
                ->setType($dataCourse['type'])
                ->setPrice($dataCourse['price']);
            $manager->persist($course);
        }
        
        $manager->flush();
    }
}