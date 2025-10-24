<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use MyEdSpace\LMS\Entity\Student;
use MyEdSpace\LMS\Entity\Course;
use MyEdSpace\LMS\Entity\Enrolment;
use MyEdSpace\LMS\Entity\Lesson;
use MyEdSpace\LMS\Entity\Homework;
use MyEdSpace\LMS\Entity\PrepMaterial;
use MyEdSpace\LMS\Service\AccessControlService;
use DateTimeImmutable;

class AccessControlServiceTest extends TestCase
{
    private AccessControlService $service;
    private Student $student;
    private Course $course;
    private Enrolment $enrolment;

    protected function setUp(): void
    {
        $this->service = new AccessControlService();
        $this->student = new Student('1342', 'Emma Watson');

        $this->course = new Course('2583', 'A-Level Biology', new DateTimeImmutable('2025-05-13'));
        $this->course->addContent(new Lesson('9001', 'Cell Structure', new DateTimeImmutable('2025-05-15 10:00')));
        $this->course->addContent(new Homework('9002', 'Label a Plant Cell'));
        $this->course->addContent(new PrepMaterial('9003', 'Biology Reading Guide'));

        $this->enrolment = new Enrolment(
            '7654',
            $this->student,
            $this->course,
            new DateTimeImmutable('2025-05-01'),
            new DateTimeImmutable('2025-05-30')
        );
    }
    public function testAccessDeniedWhenEnrolmentInactive(): void
    {
        // Try to access before enrolment starts
        $result = $this->service->canAccess(
            $this->student,
            'prep-1',
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-04-30')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('Student enrolment is not active', $result->getReason());
    }

    public function testAccessDeniedWhenCourseNotStarted(): void
    {
        // Try to access when enrolled but course hasn't started
        $result = $this->service->canAccess(
            $this->student,
            '9003',
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-05-01')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('Course has not started yet', $result->getReason());
    }

    public function testAccessDeniedWhenContentNotFound(): void
    {
        $result = $this->service->canAccess(
            $this->student,
            'non-existent-content',
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-05-13')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('Content not found', $result->getReason());
    }

    public function testAccessDeniedWhenContentNotYetAvailable(): void
    {
        // Try to access lesson before its scheduled time
        $result = $this->service->canAccess(
            $this->student,
            '9001',
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-05-15 09:59')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('Content is not yet available', $result->getReason());
    }

    public function testAccessGrantedWhenAllConditionsMet(): void
    {
        // Access prep material after course starts
        $result = $this->service->canAccess(
            $this->student,
            '9003',
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-05-13')
        );

        $this->assertTrue($result->isAllowed());
        $this->assertEquals('Access granted', $result->getReason());
    }

    public function testAccessGrantedForLessonAtScheduledTime(): void
    {
        // Access lesson at its scheduled time
        $result = $this->service->canAccess(
            $this->student,
            '9001',
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-05-15 10:01')
        );

        $this->assertTrue($result->isAllowed());
        $this->assertEquals('Access granted', $result->getReason());
    }

    public function testGetAccessibleContent(): void
    {
        // Before course starts
        $accessible = $this->service->getAccessibleContent(
            $this->student,
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-05-01')
        );
        $this->assertEmpty($accessible);

        // After course starts but before lesson time
        $accessible = $this->service->getAccessibleContent(
            $this->student,
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-05-13')
        );
        $this->assertCount(2, $accessible); // homework and prep material

        // After lesson time
        $accessible = $this->service->getAccessibleContent(
            $this->student,
            $this->course,
            $this->enrolment,
            new DateTimeImmutable('2025-05-15 10:01')
        );
        $this->assertCount(3, $accessible); // all content
    }
}
