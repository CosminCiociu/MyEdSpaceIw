<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use MyEdSpace\LMS\Controller\ContentAccessController;
use MyEdSpace\LMS\Service\LMSService;
use MyEdSpace\LMS\Service\AccessControlService;
use MyEdSpace\LMS\Service\EnrolmentService;

class ContentAccessControllerTest extends TestCase
{
    private ContentAccessController $controller;
    private LMSService $lmsService;

    protected function setUp(): void
    {
        $accessControlService = new AccessControlService();
        $enrolmentService = new EnrolmentService();
        $this->lmsService = new LMSService($accessControlService, $enrolmentService);
        $this->controller = new ContentAccessController($this->lmsService);
    }

    public function testCheckAccessReturnsAllowedResponse(): void
    {
        // Create enrolment first
        $this->lmsService->createEnrolment(
            '7654',
            new \MyEdSpace\LMS\Entity\Student('1342', 'Emma'),
            $this->createMockCourse(),
            new \DateTimeImmutable('2025-05-01'),
            new \DateTimeImmutable('2025-05-30')
        );

        $requestData = [
            'student_id' => '1342',
            'course_id' => '5874',
            'content_id' => '8003',
            'access_time' => '2025-05-13T00:00:00Z'
        ];

        $response = $this->controller->checkAccess($requestData);

        $this->assertTrue($response['allowed']);
        $this->assertEquals('Access granted', $response['reason']);
        $this->assertEquals('1342', $response['student_id']);
        $this->assertEquals('5874', $response['course_id']);
        $this->assertEquals('8003', $response['content_id']);
        $this->assertArrayHasKey('timestamp', $response);
    }

    public function testCheckAccessReturnsDeniedResponse(): void
    {
        // Create enrolment first
        $this->lmsService->createEnrolment(
            '7654',
            new \MyEdSpace\LMS\Entity\Student('1342', 'Emma'),
            $this->createMockCourse(),
            new \DateTimeImmutable('2025-05-01'),
            new \DateTimeImmutable('2025-05-30')
        );

        $requestData = [
            'student_id' => '1342',
            'course_id' => '5874',
            'content_id' => '8003',
            'access_time' => '2025-05-01T00:00:00Z' // Before course starts
        ];

        $response = $this->controller->checkAccess($requestData);

        $this->assertFalse($response['allowed']);
        $this->assertEquals('Course has not started yet', $response['reason']);
    }

    public function testCheckAccessHandlesMissingParameters(): void
    {
        $requestData = [
            'student_id' => '1342',
            // Missing required fields
        ];

        $response = $this->controller->checkAccess($requestData);

        $this->assertTrue($response['error']);
        $this->assertFalse($response['allowed']);
        $this->assertStringContainsString('Missing required field', $response['message']);
    }

    public function testGetAccessibleContentReturnsFormattedResponse(): void
    {
        // Create enrolment first
        $this->lmsService->createEnrolment(
            '7654',
            new \MyEdSpace\LMS\Entity\Student('1342', 'Emma'),
            $this->createMockCourse(),
            new \DateTimeImmutable('2025-05-01'),
            new \DateTimeImmutable('2025-05-30')
        );

        $queryParams = [
            'student_id' => '1342',
            'time' => '2025-05-15T10:01:00Z'
        ];

        $response = $this->controller->getAccessibleContent('5874', $queryParams);

        $this->assertArrayHasKey('accessible_content', $response);
        $this->assertArrayHasKey('total_count', $response);
        $this->assertEquals(3, $response['total_count']); // All content should be accessible

        // Check content structure
        $content = $response['accessible_content'][0];
        $this->assertArrayHasKey('id', $content);
        $this->assertArrayHasKey('title', $content);
        $this->assertArrayHasKey('type', $content);
    }

    public function testCreateEnrolmentReturnsSuccessResponse(): void
    {
        $requestData = [
            'student_id' => '1342',
            'course_id' => '5874',
            'start_date' => '2025-05-01T00:00:00Z',
            'end_date' => '2025-05-30T23:59:59Z'
        ];

        $response = $this->controller->createEnrolment($requestData);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('enrolment_id', $response);
        $this->assertEquals('1342', $response['student_id']);
        $this->assertEquals('5874', $response['course_id']);
    }

    public function testUpdateEnrolmentReturnsSuccessResponse(): void
    {
        // First create an enrolment
        $createResponse = $this->controller->createEnrolment([
            'student_id' => '1342',
            'course_id' => '5874',
            'start_date' => '2025-05-01T00:00:00Z',
            'end_date' => '2025-05-30T23:59:59Z'
        ]);

        $enrolmentId = $createResponse['enrolment_id'];

        $updateData = [
            'end_date' => '2025-05-20T23:59:59Z'
        ];

        $response = $this->controller->updateEnrolment($enrolmentId, $updateData);

        $this->assertTrue($response['success']);
        $this->assertEquals($enrolmentId, $response['enrolment_id']);
        $this->assertStringContainsString('2025-05-20', $response['new_end_date']);
    }

    public function testUpdateNonExistentEnrolmentReturnsError(): void
    {
        $updateData = [
            'end_date' => '2025-05-20T23:59:59Z'
        ];

        $response = $this->controller->updateEnrolment('non-existent-id', $updateData);

        $this->assertTrue($response['error']);
        $this->assertFalse($response['success']);
    }

    private function createMockCourse(): \MyEdSpace\LMS\Entity\Course
    {
        $course = new \MyEdSpace\LMS\Entity\Course('5874', 'A-Level Biology', new \DateTimeImmutable('2025-05-13'));
        $course->addContent(new \MyEdSpace\LMS\Entity\Lesson('8001', 'Cell Structure', new \DateTimeImmutable('2025-05-15 10:00')));
        $course->addContent(new \MyEdSpace\LMS\Entity\Homework('8002', 'Label a Plant Cell'));
        $course->addContent(new \MyEdSpace\LMS\Entity\PrepMaterial('8003', 'Biology Reading Guide'));
        return $course;
    }
}
