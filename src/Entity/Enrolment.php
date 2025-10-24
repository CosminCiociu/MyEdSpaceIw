<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Entity;

use DateTimeImmutable;

/**
 * Represents an enrolment linking a student to a course for a specific period
 */
class Enrolment
{
    public function __construct(
        private readonly string $id,
        private readonly Student $student,
        private readonly Course $course,
        private readonly DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getStudent(): Student
    {
        return $this->student;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * Updates the end date of the enrolment (e.g., when external systems modify it)
     */
    public function updateEndDate(DateTimeImmutable $newEndDate): void
    {
        $this->endDate = $newEndDate;
    }

    /**
     * Checks if the enrolment is active at a given datetime
     */
    public function isActiveAt(DateTimeImmutable $dateTime): bool
    {
        return $dateTime >= $this->startDate && $dateTime <= $this->endDate;
    }
}
