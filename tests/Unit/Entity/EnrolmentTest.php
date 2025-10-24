<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use MyEdSpace\LMS\Entity\Student;
use MyEdSpace\LMS\Entity\Course;
use MyEdSpace\LMS\Entity\Enrolment;
use DateTimeImmutable;

class EnrolmentTest extends TestCase
{
    private Student $student;
    private Course $course;

    protected function setUp(): void
    {
        $this->student = new Student('1342', 'Emma Watson');
        $this->course = new Course('5874', 'A-Level Biology', new DateTimeImmutable('2025-05-13'));
    }

    public function testEnrolmentCreation(): void
    {
        $startDate = new DateTimeImmutable('2025-05-01');
        $endDate = new DateTimeImmutable('2025-05-30');

        $enrolment = new Enrolment('6543', $this->student, $this->course, $startDate, $endDate);

        $this->assertEquals('6543', $enrolment->getId());
        $this->assertSame($this->student, $enrolment->getStudent());
        $this->assertSame($this->course, $enrolment->getCourse());
        $this->assertEquals($startDate, $enrolment->getStartDate());
        $this->assertEquals($endDate, $enrolment->getEndDate());
    }

    public function testEnrolmentActivity(): void
    {
        $startDate = new DateTimeImmutable('2025-05-01');
        $endDate = new DateTimeImmutable('2025-05-30');

        $enrolment = new Enrolment('7890', $this->student, $this->course, $startDate, $endDate);

        // Before enrolment starts
        $this->assertFalse($enrolment->isActiveAt(new DateTimeImmutable('2025-04-30')));

        // Enrolment start date
        $this->assertTrue($enrolment->isActiveAt(new DateTimeImmutable('2025-05-01')));

        // During enrolment
        $this->assertTrue($enrolment->isActiveAt(new DateTimeImmutable('2025-05-15')));

        // Enrolment end date
        $this->assertTrue($enrolment->isActiveAt(new DateTimeImmutable('2025-05-30')));

        // After enrolment ends
        $this->assertFalse($enrolment->isActiveAt(new DateTimeImmutable('2025-05-31')));
    }

    public function testUpdateEndDate(): void
    {
        $startDate = new DateTimeImmutable('2025-05-01');
        $endDate = new DateTimeImmutable('2025-05-30');

        $enrolment = new Enrolment('9876', $this->student, $this->course, $startDate, $endDate);

        // Initially active on May 25th
        $this->assertTrue($enrolment->isActiveAt(new DateTimeImmutable('2025-05-25')));

        // Update end date to May 20th (shortening enrolment)
        $newEndDate = new DateTimeImmutable('2025-05-20');
        $enrolment->updateEndDate($newEndDate);

        $this->assertEquals($newEndDate, $enrolment->getEndDate());

        // Now not active on May 25th
        $this->assertFalse($enrolment->isActiveAt(new DateTimeImmutable('2025-05-25')));
        $this->assertTrue($enrolment->isActiveAt(new DateTimeImmutable('2025-05-20')));
        $this->assertFalse($enrolment->isActiveAt(new DateTimeImmutable('2025-05-21')));
    }
}
