<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Service;

use MyEdSpace\LMS\Entity\Student;
use MyEdSpace\LMS\Entity\Course;
use MyEdSpace\LMS\Entity\ContentItem;
use MyEdSpace\LMS\Service\AccessControlService;
use MyEdSpace\LMS\Service\EnrolmentService;
use MyEdSpace\LMS\Service\AccessResult;
use DateTimeImmutable;

/**
 * Main LMS service that coordinates access control and enrolment management
 * This would be the main entry point for API endpoints
 */
class LMSService
{
    public function __construct(
        private readonly AccessControlService $accessControlService,
        private readonly EnrolmentService $enrolmentService
    ) {}

    /**
     * Checks if a student can access specific content
     * 
     * @param string $studentId The student's ID
     * @param string $courseId The course ID
     * @param string $contentId The content ID to access
     * @param DateTimeImmutable $accessTime When the access is attempted
     */
    public function checkContentAccess(
        Student $student,
        Course $course,
        string $contentId,
        DateTimeImmutable $accessTime
    ): AccessResult {
        // Find active enrolment
        $enrolment = $this->enrolmentService->findActiveEnrolment($student, $course, $accessTime);

        if ($enrolment === null) {
            return new AccessResult(false, 'No active enrolment found');
        }

        return $this->accessControlService->canAccess(
            $student,
            $contentId,
            $course,
            $enrolment,
            $accessTime
        );
    }

    /**
     * Gets all content accessible to a student at a given time
     * 
     * @return ContentItem[]
     */
    public function getAccessibleContent(
        Student $student,
        Course $course,
        DateTimeImmutable $accessTime
    ): array {
        $enrolment = $this->enrolmentService->findActiveEnrolment($student, $course, $accessTime);

        if ($enrolment === null) {
            return [];
        }

        return $this->accessControlService->getAccessibleContent(
            $student,
            $course,
            $enrolment,
            $accessTime
        );
    }

    /**
     * Creates a new enrolment
     */
    public function createEnrolment(
        string $enrolmentId,
        Student $student,
        Course $course,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): bool {
        $this->enrolmentService->enroll($enrolmentId, $student, $course, $startDate, $endDate);
        return true;
    }

    /**
     * Updates an enrolment's end date (simulating external system changes)
     */
    public function updateEnrolmentEndDate(string $enrolmentId, DateTimeImmutable $newEndDate): bool
    {
        return $this->enrolmentService->updateEnrolmentEndDate($enrolmentId, $newEndDate);
    }

    /**
     * Gets the enrolment service for direct access when needed
     */
    public function getEnrolmentService(): EnrolmentService
    {
        return $this->enrolmentService;
    }

    /**
     * Gets the access control service for direct access when needed
     */
    public function getAccessControlService(): AccessControlService
    {
        return $this->accessControlService;
    }
}
