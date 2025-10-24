<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Entity;

use DateTimeImmutable;

/**
 * Represents a course with content and scheduling information
 */
class Course
{
    /** @var ContentItem[] */
    private array $contentItems = [];

    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly DateTimeImmutable $startDate,
        private readonly ?DateTimeImmutable $endDate = null
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * Adds content to the course
     */
    public function addContent(ContentItem $content): void
    {
        $this->contentItems[$content->getId()] = $content;
    }

    /**
     * Gets a specific content item by ID
     */
    public function getContent(string $contentId): ?ContentItem
    {
        return $this->contentItems[$contentId] ?? null;
    }

    /**
     * Gets all content items
     * 
     * @return ContentItem[]
     */
    public function getAllContent(): array
    {
        return array_values($this->contentItems);
    }

    /**
     * Checks if the course has started at a given datetime
     */
    public function hasStartedAt(DateTimeImmutable $dateTime): bool
    {
        return $dateTime >= $this->startDate;
    }

    /**
     * Checks if the course has ended at a given datetime
     */
    public function hasEndedAt(DateTimeImmutable $dateTime): bool
    {
        return $this->endDate !== null && $dateTime > $this->endDate;
    }

    /**
     * Checks if the course is running at a given datetime
     */
    public function isRunningAt(DateTimeImmutable $dateTime): bool
    {
        return $this->hasStartedAt($dateTime) && !$this->hasEndedAt($dateTime);
    }
}
