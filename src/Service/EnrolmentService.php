<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Service;

use MyEdSpace\LMS\Entity\Student;
use MyEdSpace\LMS\Entity\Course;
use MyEdSpace\LMS\Entity\Enrolment;
use DateTimeImmutable;

/**
 * Service for managing student enrolments
 */
class EnrolmentService
{
    /** @var Enrolment[] */
    private array $enrolments = [];

    /**
     * Creates a new enrolment
     */
    public function enroll(
        string $enrolmentId,
        Student $student,
        Course $course,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): Enrolment {
        $enrolment = new Enrolment($enrolmentId, $student, $course, $startDate, $endDate);
        $this->enrolments[$enrolmentId] = $enrolment;

        return $enrolment;
    }

    /**
     * Gets an enrolment by ID
     */
    public function getEnrolment(string $enrolmentId): ?Enrolment
    {
        return $this->enrolments[$enrolmentId] ?? null;
    }

    /**
     * Finds an active enrolment for a student in a specific course at a given time
     */
    public function findActiveEnrolment(
        Student $student,
        Course $course,
        DateTimeImmutable $dateTime
    ): ?Enrolment {
        foreach ($this->enrolments as $enrolment) {
            if (
                $enrolment->getStudent()->getId() === $student->getId() &&
                $enrolment->getCourse()->getId() === $course->getId() &&
                $enrolment->isActiveAt($dateTime)
            ) {
                return $enrolment;
            }
        }

        return null;
    }

    /**
     * Updates an enrolment's end date (simulating external system changes)
     */
    public function updateEnrolmentEndDate(string $enrolmentId, DateTimeImmutable $newEndDate): bool
    {
        $enrolment = $this->getEnrolment($enrolmentId);
        if ($enrolment === null) {
            return false;
        }

        $enrolment->updateEndDate($newEndDate);
        return true;
    }

    /**
     * Gets all enrolments for a student
     * 
     * @return Enrolment[]
     */
    public function getStudentEnrolments(Student $student): array
    {
        return array_filter(
            $this->enrolments,
            fn(Enrolment $enrolment) => $enrolment->getStudent()->getId() === $student->getId()
        );
    }
}
