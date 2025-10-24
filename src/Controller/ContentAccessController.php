<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Controller;

use MyEdSpace\LMS\Service\LMSService;
use MyEdSpace\LMS\Entity\Student;
use MyEdSpace\LMS\Entity\Course;
use DateTimeImmutable;
use Exception;

/**
 * Example API Controller demonstrating how the LMS backend can be integrated with HTTP endpoints
 * 
 * This is a demonstration controller showing API-ready design patterns.
 * In a real application, this would be integrated with a framework like Symfony, Laravel, or Slim.
 */
class ContentAccessController
{
    public function __construct(
        private readonly LMSService $lmsService
    ) {}

    /**
     * POST /api/content/access
     * 
     * Checks if a student can access specific content
     * 
     * Request body:
     * {
     *   "student_id": "1342",
     *   "course_id": "5874", 
     *   "content_id": "8001",
     *   "access_time": "2025-05-15T10:01:00Z"
     * }
     * 
     * Response:
     * {
     *   "allowed": true,
     *   "reason": "Access granted",
     *   "timestamp": "2025-05-15T10:01:00Z"
     * }
     */
    public function checkAccess(array $requestData): array
    {
        try {
            // Validate required fields
            $this->validateAccessRequest($requestData);

            // Create entities (in real app, these would come from repositories/database)
            $student = $this->getStudentById($requestData['student_id']);
            $course = $this->getCourseById($requestData['course_id']);
            $accessTime = new DateTimeImmutable($requestData['access_time']);

            // Check access using our business logic
            $result = $this->lmsService->checkContentAccess(
                $student,
                $course,
                $requestData['content_id'],
                $accessTime
            );

            return [
                'allowed' => $result->isAllowed(),
                'reason' => $result->getReason(),
                'timestamp' => $accessTime->format('c'),
                'student_id' => $student->getId(),
                'course_id' => $course->getId(),
                'content_id' => $requestData['content_id']
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'allowed' => false
            ];
        }
    }

    /**
     * GET /api/courses/{courseId}/accessible-content?student_id={studentId}&time={timestamp}
     * 
     * Gets all content accessible to a student at a given time
     * 
     * Response:
     * {
     *   "accessible_content": [
     *     {
     *       "id": "8001",
     *       "title": "Cell Structure",
     *       "type": "lesson"
     *     },
     *     {
     *       "id": "8002", 
     *       "title": "Label a Plant Cell",
     *       "type": "homework"
     *     }
     *   ],
     *   "total_count": 2,
     *   "timestamp": "2025-05-15T10:01:00Z"
     * }
     */
    public function getAccessibleContent(string $courseId, array $queryParams): array
    {
        try {
            // Validate query parameters
            if (!isset($queryParams['student_id'], $queryParams['time'])) {
                throw new Exception('Missing required parameters: student_id, time');
            }

            $student = $this->getStudentById($queryParams['student_id']);
            $course = $this->getCourseById($courseId);
            $accessTime = new DateTimeImmutable($queryParams['time']);

            // Get accessible content using our business logic
            $accessibleContent = $this->lmsService->getAccessibleContent(
                $student,
                $course,
                $accessTime
            );

            // Format for API response
            $formattedContent = [];
            foreach ($accessibleContent as $content) {
                $formattedContent[] = [
                    'id' => $content->getId(),
                    'title' => $content->getTitle(),
                    'type' => $this->getContentType($content)
                ];
            }

            return [
                'accessible_content' => $formattedContent,
                'total_count' => count($formattedContent),
                'timestamp' => $accessTime->format('c'),
                'student_id' => $student->getId(),
                'course_id' => $course->getId()
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'accessible_content' => []
            ];
        }
    }

    /**
     * POST /api/enrolments
     * 
     * Creates a new enrolment
     * 
     * Request body:
     * {
     *   "student_id": "1342",
     *   "course_id": "5874",
     *   "start_date": "2025-05-01T00:00:00Z",
     *   "end_date": "2025-05-30T23:59:59Z"
     * }
     */
    public function createEnrolment(array $requestData): array
    {
        try {
            $this->validateEnrolmentRequest($requestData);

            $student = $this->getStudentById($requestData['student_id']);
            $course = $this->getCourseById($requestData['course_id']);
            $startDate = new DateTimeImmutable($requestData['start_date']);
            $endDate = new DateTimeImmutable($requestData['end_date']);

            // Generate enrolment ID (in real app, this might be auto-generated)
            $enrolmentId = uniqid('enrol_');

            $success = $this->lmsService->createEnrolment(
                $enrolmentId,
                $student,
                $course,
                $startDate,
                $endDate
            );

            return [
                'success' => $success,
                'enrolment_id' => $enrolmentId,
                'student_id' => $student->getId(),
                'course_id' => $course->getId(),
                'start_date' => $startDate->format('c'),
                'end_date' => $endDate->format('c')
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'success' => false
            ];
        }
    }

    /**
     * PUT /api/enrolments/{enrolmentId}
     * 
     * Updates an enrolment's end date
     * 
     * Request body:
     * {
     *   "end_date": "2025-05-20T23:59:59Z"
     * }
     */
    public function updateEnrolment(string $enrolmentId, array $requestData): array
    {
        try {
            if (!isset($requestData['end_date'])) {
                throw new Exception('Missing required field: end_date');
            }

            $newEndDate = new DateTimeImmutable($requestData['end_date']);

            $success = $this->lmsService->updateEnrolmentEndDate($enrolmentId, $newEndDate);

            if (!$success) {
                throw new Exception('Enrolment not found or update failed');
            }

            return [
                'success' => true,
                'enrolment_id' => $enrolmentId,
                'new_end_date' => $newEndDate->format('c'),
                'message' => 'Enrolment updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'success' => false
            ];
        }
    }

    // Private helper methods (in real app, these would be repository calls)

    private function validateAccessRequest(array $data): void
    {
        $required = ['student_id', 'course_id', 'content_id', 'access_time'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
    }

    private function validateEnrolmentRequest(array $data): void
    {
        $required = ['student_id', 'course_id', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
    }

    private function getStudentById(string $studentId): Student
    {
        // In a real application, this would query a database or repository
        // For demo purposes, we'll create mock data
        return new Student($studentId, 'Emma Watson');
    }

    private function getCourseById(string $courseId): Course
    {
        // In a real application, this would query a database or repository
        // For demo purposes, we'll create mock data with content
        $course = new Course($courseId, 'A-Level Biology', new DateTimeImmutable('2025-05-13'));

        // Add mock content (in real app, this would come from database)
        $course->addContent(new \MyEdSpace\LMS\Entity\Lesson('8001', 'Cell Structure', new DateTimeImmutable('2025-05-15 10:00')));
        $course->addContent(new \MyEdSpace\LMS\Entity\Homework('8002', 'Label a Plant Cell'));
        $course->addContent(new \MyEdSpace\LMS\Entity\PrepMaterial('8003', 'Biology Reading Guide'));

        return $course;
    }

    private function getContentType(object $content): string
    {
        return match (get_class($content)) {
            'MyEdSpace\LMS\Entity\Lesson' => 'lesson',
            'MyEdSpace\LMS\Entity\Homework' => 'homework',
            'MyEdSpace\LMS\Entity\PrepMaterial' => 'prep_material',
            default => 'unknown'
        };
    }
}
