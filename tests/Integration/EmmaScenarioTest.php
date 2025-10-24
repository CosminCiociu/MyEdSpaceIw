<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Tests\Integration;

use PHPUnit\Framework\TestCase;
use MyEdSpace\LMS\Entity\Student;
use MyEdSpace\LMS\Entity\Course;
use MyEdSpace\LMS\Entity\Lesson;
use MyEdSpace\LMS\Entity\Homework;
use MyEdSpace\LMS\Entity\PrepMaterial;
use MyEdSpace\LMS\Service\AccessControlService;
use MyEdSpace\LMS\Service\EnrolmentService;
use MyEdSpace\LMS\Service\LMSService;
use DateTimeImmutable;

/**
 * Integration test that validates the exact scenario from the requirements:
 * 
 * Course: "A-Level Biology"
 * Start date: 13/05/2025
 * End date: 12/06/2025
 * Includes:
 * - Lesson: "Cell Structure" — 15/05/2025 10:00
 * - Homework: "Label a Plant Cell"
 * - Prep Material: "Biology Reading Guide"
 * 
 * Student: Emma
 * Initial enrolment: 01/05/2025 → 30/05/2025
 */
class EmmaScenarioTest extends TestCase
{
    private LMSService $lmsService;
    private Student $emma;
    private Course $bioCourse;
    private string $enrolmentId;

    protected function setUp(): void
    {
        // Set up services
        $accessControlService = new AccessControlService();
        $enrolmentService = new EnrolmentService();
        $this->lmsService = new LMSService($accessControlService, $enrolmentService);

        // Create Emma
        $this->emma = new Student('1342', 'Emma');

        // Create A-Level Biology course
        $this->bioCourse = new Course(
            '5874',
            'A-Level Biology',
            new DateTimeImmutable('2025-05-13'),
            new DateTimeImmutable('2025-06-12')
        );

        // Add course content
        $this->bioCourse->addContent(new Lesson(
            '8001',
            'Cell Structure',
            new DateTimeImmutable('2025-05-15 10:00')
        ));
        $this->bioCourse->addContent(new Homework('8002', 'Label a Plant Cell'));
        $this->bioCourse->addContent(new PrepMaterial('8003', 'Biology Reading Guide'));

        // Create initial enrolment: 01/05/2025 → 30/05/2025
        $this->enrolmentId = '7654';
        $this->lmsService->createEnrolment(
            $this->enrolmentId,
            $this->emma,
            $this->bioCourse,
            new DateTimeImmutable('2025-05-01'),
            new DateTimeImmutable('2025-05-30')
        );
    }

    /**
     * 1. On 01/05/2025, Emma tries to access Prep Material → ❌ Denied (course not started)
     */
    public function testScenario1_PrepMaterialBeforeCourseStarts(): void
    {
        $result = $this->lmsService->checkContentAccess(
            $this->emma,
            $this->bioCourse,
            '8003',
            new DateTimeImmutable('2025-05-01')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('Course has not started yet', $result->getReason());
    }

    /**
     * 2. On 13/05/2025, she accesses Prep Material → ✅ Allowed
     */
    public function testScenario2_PrepMaterialAfterCourseStarts(): void
    {
        $result = $this->lmsService->checkContentAccess(
            $this->emma,
            $this->bioCourse,
            '8003',
            new DateTimeImmutable('2025-05-13')
        );

        $this->assertTrue($result->isAllowed());
        $this->assertEquals('Access granted', $result->getReason());
    }

    /**
     * 3. On 15/05/2025 at 10:01, she accesses the Lesson → ✅ Allowed
     */
    public function testScenario3_LessonAfterScheduledTime(): void
    {
        $result = $this->lmsService->checkContentAccess(
            $this->emma,
            $this->bioCourse,
            '8001',
            new DateTimeImmutable('2025-05-15 10:01')
        );

        $this->assertTrue($result->isAllowed());
        $this->assertEquals('Access granted', $result->getReason());
    }

    /**
     * 4. On 20/05/2025, an external system shortens Emma's enrolment → new end date is 20/05/2025
     * 5. On 21/05/2025, she tries to access Homework → ❌ Denied (enrolment expired early)
     */
    public function testScenario4and5_EnrolmentShortenedAndAccessDenied(): void
    {
        // First, shorten the enrolment (external system action)
        $success = $this->lmsService->updateEnrolmentEndDate(
            $this->enrolmentId,
            new DateTimeImmutable('2025-05-20')
        );
        $this->assertTrue($success);

        // Then try to access homework on 21/05/2025
        $result = $this->lmsService->checkContentAccess(
            $this->emma,
            $this->bioCourse,
            '8002',
            new DateTimeImmutable('2025-05-21')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('No active enrolment found', $result->getReason());
    }

    /**
     * 6. On 30/05/2025, she tries again → ❌ Denied
     */
    public function testScenario6_StillDeniedOnOriginalEndDate(): void
    {
        // Ensure enrolment is still shortened from previous test
        $this->lmsService->updateEnrolmentEndDate(
            $this->enrolmentId,
            new DateTimeImmutable('2025-05-20')
        );

        $result = $this->lmsService->checkContentAccess(
            $this->emma,
            $this->bioCourse,
            '8002',
            new DateTimeImmutable('2025-05-30')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('No active enrolment found', $result->getReason());
    }

    /**
     * 7. On 10/06/2025, the course is still running, but Emma is no longer enrolled → ❌ Denied
     */
    public function testScenario7_CourseRunningButNoEnrolment(): void
    {
        // Ensure enrolment is still shortened
        $this->lmsService->updateEnrolmentEndDate(
            $this->enrolmentId,
            new DateTimeImmutable('2025-05-20')
        );

        // Course is still running (ends 12/06/2025)
        $this->assertTrue($this->bioCourse->isRunningAt(new DateTimeImmutable('2025-06-10')));

        $result = $this->lmsService->checkContentAccess(
            $this->emma,
            $this->bioCourse,
            '8002',
            new DateTimeImmutable('2025-06-10')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('No active enrolment found', $result->getReason());
    }

    /**
     * Test getting all accessible content at different times
     */
    public function testAccessibleContentProgression(): void
    {
        // On course start date (13/05/2025) - should have homework and prep material
        $accessible = $this->lmsService->getAccessibleContent(
            $this->emma,
            $this->bioCourse,
            new DateTimeImmutable('2025-05-13')
        );

        $this->assertCount(2, $accessible);
        $contentIds = array_map(fn($content) => $content->getId(), $accessible);
        $this->assertContains('8002', $contentIds);
        $this->assertContains('8003', $contentIds);
        $this->assertNotContains('8001', $contentIds);

        // After lesson time (15/05/2025 10:01) - should have all content
        $accessible = $this->lmsService->getAccessibleContent(
            $this->emma,
            $this->bioCourse,
            new DateTimeImmutable('2025-05-15 10:01')
        );

        $this->assertCount(3, $accessible);
        $contentIds = array_map(fn($content) => $content->getId(), $accessible);
        $this->assertContains('8002', $contentIds);
        $this->assertContains('8003', $contentIds);
        $this->assertContains('8001', $contentIds);
    }

    /**
     * Additional test: Verify lesson is not accessible before its scheduled time
     */
    public function testLessonNotAccessibleBeforeScheduledTime(): void
    {
        // Try to access lesson before 10:00 on 15/05/2025
        $result = $this->lmsService->checkContentAccess(
            $this->emma,
            $this->bioCourse,
            '8001',
            new DateTimeImmutable('2025-05-15 09:59')
        );

        $this->assertFalse($result->isAllowed());
        $this->assertEquals('Content is not yet available', $result->getReason());
    }
}
