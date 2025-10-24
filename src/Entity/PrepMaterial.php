<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Entity;

use DateTimeImmutable;

/**
 * Represents preparatory materials available from course start
 */
class PrepMaterial extends ContentItem
{
    /**
     * Prep materials are available from course start onwards
     */
    public function isAvailableAt(DateTimeImmutable $currentTime, DateTimeImmutable $courseStartTime): bool
    {
        return $currentTime >= $courseStartTime;
    }
}
