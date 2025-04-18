<?php

namespace App\Service;

use App\Dto\CourseCreateDto;
use App\Dto\CourseEditDto;
use App\Entity\Course;
use App\Enum\EnumCourseType;
use App\Exception\IsExistsCourseException;
use App\Repository\CourseRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CourseService
{
    public function __construct(
        private CourseRepository $courseRepository,
    ) {
    }

    public function create(CourseCreateDto $courseDto): bool
    {
        $course = $this->courseRepository->findOneBy(['chars_code' => $courseDto->getCode()]);
        if ($course !== null) {
            throw new IsExistsCourseException;
        }
        $course = new Course();
        $course->setTitle($courseDto->getTitle())
            ->setCharsCode($courseDto->getCode())
            ->setType(EnumCourseType::byString($courseDto->getType())->code())
            ->setPrice($courseDto->getPrice());
        return $this->courseRepository->persistCourse($course);
    }

    public function edit(string $code, CourseEditDto $courseDto): bool
    {
        $course = $this->courseRepository->findOneBy(['chars_code' => $code]);
        if ($course === null) {
            throw new HttpException(404, 'Курс не найден');
        }
        $course = $this->updateFieldCourse($course, $courseDto);
        return $this->courseRepository->persistCourse($course);
    }

    private function updateFieldCourse(Course $course, CourseEditDto $courseDto): Course
    {
        $type = null;
        if ($courseDto->getType() !== null) {
            $type = EnumCourseType::byString($courseDto->getType())->code();
        }
        $course->setTitle($courseDto->getTitle() ?? $course->getTitle())
            ->setCharsCode($courseDto->getCode() ?? $course->getCharsCode())
            ->setType($type ?? $course->getType())
            ->setPrice($courseDto->getPrice() ?? $course->getPrice());
        return $course;
    }
}