<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Entity;

use DateTimeImmutable;

/**
 * Represents homework available from course start
 */
class Homework extends ContentItem
{
    /**
     * Homework is available from course start onwards
     */
    public function isAvailableAt(DateTimeImmutable $currentTime, DateTimeImmutable $courseStartTime): bool
    {
        return $currentTime >= $courseStartTime;
    }
}
