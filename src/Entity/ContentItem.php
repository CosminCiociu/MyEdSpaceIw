<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Entity;

use DateTimeImmutable;

/**
 * Abstract base class for course content items
 */
abstract class ContentItem
{
    public function __construct(
        private readonly string $id,
        private readonly string $title
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Determines if this content item is available at the given datetime
     * for a course that started at the given datetime
     */
    abstract public function isAvailableAt(DateTimeImmutable $currentTime, DateTimeImmutable $courseStartTime): bool;
}
