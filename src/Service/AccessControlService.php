<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Service;

use MyEdSpace\LMS\Entity\Student;
use MyEdSpace\LMS\Entity\Course;
use MyEdSpace\LMS\Entity\ContentItem;
use MyEdSpace\LMS\Entity\Enrolment;
use DateTimeImmutable;

/**
 * Result of an access control check
 */
class AccessResult
{
    public function __construct(
        private readonly bool $allowed,
        private readonly string $reason
    ) {}

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}

/**
 * Service responsible for determining content access permissions
 */
class AccessControlService
{
    /**
     * Determines if a student can access specific content at a given time
     */
    public function canAccess(
        Student $student,
        string $contentId,
        Course $course,
        Enrolment $enrolment,
        DateTimeImmutable $accessTime
    ): AccessResult {
        // 1. Check if student is currently enrolled
        if (!$enrolment->isActiveAt($accessTime)) {
            return new AccessResult(false, 'Student enrolment is not active');
        }

        // 2. Check if the course has started
        if (!$course->hasStartedAt($accessTime)) {
            return new AccessResult(false, 'Course has not started yet');
        }

        // 3. Check if the specific content exists
        $content = $course->getContent($contentId);
        if ($content === null) {
            return new AccessResult(false, 'Content not found');
        }

        // 4. Check content-specific availability rules
        if (!$content->isAvailableAt($accessTime, $course->getStartDate())) {
            return new AccessResult(false, 'Content is not yet available');
        }

        return new AccessResult(true, 'Access granted');
    }

    /**
     * Gets all accessible content for a student at a given time
     * 
     * @return ContentItem[]
     */
    public function getAccessibleContent(
        Student $student,
        Course $course,
        Enrolment $enrolment,
        DateTimeImmutable $accessTime
    ): array {
        $accessibleContent = [];

        // Basic checks first
        if (!$enrolment->isActiveAt($accessTime) || !$course->hasStartedAt($accessTime)) {
            return $accessibleContent;
        }

        foreach ($course->getAllContent() as $content) {
            if ($content->isAvailableAt($accessTime, $course->getStartDate())) {
                $accessibleContent[] = $content;
            }
        }

        return $accessibleContent;
    }
}
