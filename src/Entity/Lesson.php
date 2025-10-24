<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Entity;

use DateTimeImmutable;

/**
 * Represents a lesson with a specific scheduled datetime
 */
class Lesson extends ContentItem
{
    public function __construct(
        string $id,
        string $title,
        private readonly DateTimeImmutable $scheduledDateTime
    ) {
        parent::__construct($id, $title);
    }

    public function getScheduledDateTime(): DateTimeImmutable
    {
        return $this->scheduledDateTime;
    }

    /**
     * Lessons are only available from their scheduled datetime onwards
     */
    public function isAvailableAt(DateTimeImmutable $currentTime, DateTimeImmutable $courseStartTime): bool
    {
        return $currentTime >= $this->scheduledDateTime;
    }
}
