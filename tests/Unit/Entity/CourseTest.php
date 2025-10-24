<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use MyEdSpace\LMS\Entity\Course;
use MyEdSpace\LMS\Entity\Lesson;
use MyEdSpace\LMS\Entity\Homework;
use MyEdSpace\LMS\Entity\PrepMaterial;
use DateTimeImmutable;

class CourseTest extends TestCase
{
    public function testCourseCreation(): void
    {
        $startDate = new DateTimeImmutable('2025-05-13');
        $endDate = new DateTimeImmutable('2025-06-12');

        $course = new Course('1342', 'A-Level Biology', $startDate, $endDate);

        $this->assertEquals('1342', $course->getId());
        $this->assertEquals('A-Level Biology', $course->getTitle());
        $this->assertEquals($startDate, $course->getStartDate());
        $this->assertEquals($endDate, $course->getEndDate());
    }
    public function testCourseWithNoEndDate(): void
    {
        $startDate = new DateTimeImmutable('2025-05-13');

        $course = new Course('2583', 'A-Level Biology', $startDate);

        $this->assertNull($course->getEndDate());
    }

    public function testAddingContent(): void
    {
        $course = new Course('3741', 'A-Level Biology', new DateTimeImmutable('2025-05-13'));

        $lesson = new Lesson('8001', 'Cell Structure', new DateTimeImmutable('2025-05-15 10:00'));
        $homework = new Homework('8002', 'Label a Plant Cell');
        $prepMaterial = new PrepMaterial('8003', 'Biology Reading Guide');

        $course->addContent($lesson);
        $course->addContent($homework);
        $course->addContent($prepMaterial);

        $this->assertSame($lesson, $course->getContent('8001'));
        $this->assertSame($homework, $course->getContent('8002'));
        $this->assertSame($prepMaterial, $course->getContent('8003'));
        $this->assertNull($course->getContent('non-existent'));
        $allContent = $course->getAllContent();
        $this->assertCount(3, $allContent);
        $this->assertContains($lesson, $allContent);
        $this->assertContains($homework, $allContent);
        $this->assertContains($prepMaterial, $allContent);
    }

    public function testCourseScheduling(): void
    {
        $startDate = new DateTimeImmutable('2025-05-13');
        $endDate = new DateTimeImmutable('2025-06-12');
        $course = new Course('4926', 'A-Level Biology', $startDate, $endDate);

        // Before course starts
        $this->assertFalse($course->hasStartedAt(new DateTimeImmutable('2025-05-12')));
        $this->assertFalse($course->isRunningAt(new DateTimeImmutable('2025-05-12')));

        // Course start date
        $this->assertTrue($course->hasStartedAt(new DateTimeImmutable('2025-05-13')));
        $this->assertTrue($course->isRunningAt(new DateTimeImmutable('2025-05-13')));

        // During course
        $this->assertTrue($course->hasStartedAt(new DateTimeImmutable('2025-05-20')));
        $this->assertTrue($course->isRunningAt(new DateTimeImmutable('2025-05-20')));

        // Course end date (still running)
        $this->assertTrue($course->hasStartedAt(new DateTimeImmutable('2025-06-12')));
        $this->assertTrue($course->isRunningAt(new DateTimeImmutable('2025-06-12')));
        $this->assertFalse($course->hasEndedAt(new DateTimeImmutable('2025-06-12')));

        // After course ends
        $this->assertTrue($course->hasStartedAt(new DateTimeImmutable('2025-06-13')));
        $this->assertTrue($course->hasEndedAt(new DateTimeImmutable('2025-06-13')));
        $this->assertFalse($course->isRunningAt(new DateTimeImmutable('2025-06-13')));
    }
}
