<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use MyEdSpace\LMS\Entity\Lesson;
use MyEdSpace\LMS\Entity\Homework;
use MyEdSpace\LMS\Entity\PrepMaterial;
use DateTimeImmutable;

class ContentItemTest extends TestCase
{
    public function testLessonAvailability(): void
    {
        $lessonDateTime = new DateTimeImmutable('2025-05-15 10:00');
        $courseStartDate = new DateTimeImmutable('2025-05-13');

        $lesson = new Lesson('4532', 'Cell Structure', $lessonDateTime);

        // Before lesson scheduled time
        $this->assertFalse($lesson->isAvailableAt(
            new DateTimeImmutable('2025-05-15 09:59'),
            $courseStartDate
        ));

        // At lesson scheduled time
        $this->assertTrue($lesson->isAvailableAt(
            new DateTimeImmutable('2025-05-15 10:00'),
            $courseStartDate
        ));

        // After lesson scheduled time
        $this->assertTrue($lesson->isAvailableAt(
            new DateTimeImmutable('2025-05-15 10:01'),
            $courseStartDate
        ));
    }

    public function testHomeworkAvailability(): void
    {
        $courseStartDate = new DateTimeImmutable('2025-05-13');
        $homework = new Homework('5643', 'Label a Plant Cell');

        // Before course starts
        $this->assertFalse($homework->isAvailableAt(
            new DateTimeImmutable('2025-05-12'),
            $courseStartDate
        ));

        // From course start onwards
        $this->assertTrue($homework->isAvailableAt(
            new DateTimeImmutable('2025-05-13'),
            $courseStartDate
        ));

        $this->assertTrue($homework->isAvailableAt(
            new DateTimeImmutable('2025-05-20'),
            $courseStartDate
        ));
    }

    public function testPrepMaterialAvailability(): void
    {
        $courseStartDate = new DateTimeImmutable('2025-05-13');
        $prepMaterial = new PrepMaterial('6754', 'Biology Reading Guide');

        // Before course starts
        $this->assertFalse($prepMaterial->isAvailableAt(
            new DateTimeImmutable('2025-05-12'),
            $courseStartDate
        ));

        // From course start onwards
        $this->assertTrue($prepMaterial->isAvailableAt(
            new DateTimeImmutable('2025-05-13'),
            $courseStartDate
        ));

        $this->assertTrue($prepMaterial->isAvailableAt(
            new DateTimeImmutable('2025-05-20'),
            $courseStartDate
        ));
    }
}
