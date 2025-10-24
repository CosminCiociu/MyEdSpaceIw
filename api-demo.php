<?php

/**
 * API Controller Demonstration
 * 
 * This demonstrates how the LMS backend integrates with API endpoints
 * through the ContentAccessController.
 */

require_once __DIR__ . '/vendor/autoload.php';

use MyEdSpace\LMS\Controller\ContentAccessController;
use MyEdSpace\LMS\Service\AccessControlService;
use MyEdSpace\LMS\Service\EnrolmentService;
use MyEdSpace\LMS\Service\LMSService;

echo "=== LMS API Controller Demonstration ===\n\n";

// Initialize services (same as before)
$accessControlService = new AccessControlService();
$enrolmentService = new EnrolmentService();
$lmsService = new LMSService($accessControlService, $enrolmentService);

// Create the API controller
$controller = new ContentAccessController($lmsService);

echo "✨ Created API Controller with LMS Service\n\n";

// Simulate API requests and responses

echo "=== 1. Creating Enrolment (POST /api/enrolments) ===\n";
$createEnrolmentRequest = [
    'student_id' => '1342',
    'course_id' => '5874',
    'start_date' => '2025-05-01T00:00:00Z',
    'end_date' => '2025-05-30T23:59:59Z'
];

echo "Request: " . json_encode($createEnrolmentRequest, JSON_PRETTY_PRINT) . "\n";
$enrolmentResponse = $controller->createEnrolment($createEnrolmentRequest);
echo "Response: " . json_encode($enrolmentResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "=== 2. Check Content Access - Before Course Starts (POST /api/content/access) ===\n";
$accessRequest1 = [
    'student_id' => '1342',
    'course_id' => '5874',
    'content_id' => '8003', // Biology Reading Guide
    'access_time' => '2025-05-01T00:00:00Z'
];

echo "Request: " . json_encode($accessRequest1, JSON_PRETTY_PRINT) . "\n";
$accessResponse1 = $controller->checkAccess($accessRequest1);
echo "Response: " . json_encode($accessResponse1, JSON_PRETTY_PRINT) . "\n\n";

echo "=== 3. Check Content Access - After Course Starts (POST /api/content/access) ===\n";
$accessRequest2 = [
    'student_id' => '1342',
    'course_id' => '5874',
    'content_id' => '8003', // Biology Reading Guide
    'access_time' => '2025-05-13T00:00:00Z'
];

echo "Request: " . json_encode($accessRequest2, JSON_PRETTY_PRINT) . "\n";
$accessResponse2 = $controller->checkAccess($accessRequest2);
echo "Response: " . json_encode($accessResponse2, JSON_PRETTY_PRINT) . "\n\n";

echo "=== 4. Get Accessible Content (GET /api/courses/5874/accessible-content) ===\n";
$queryParams = [
    'student_id' => '1342',
    'time' => '2025-05-15T10:01:00Z'
];

echo "Query Parameters: " . json_encode($queryParams, JSON_PRETTY_PRINT) . "\n";
$contentResponse = $controller->getAccessibleContent('5874', $queryParams);
echo "Response: " . json_encode($contentResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "=== 5. Update Enrolment (PUT /api/enrolments/{id}) ===\n";
$updateRequest = [
    'end_date' => '2025-05-20T23:59:59Z'
];

$enrolmentId = $enrolmentResponse['enrolment_id'] ?? 'test_enrol_123';
echo "Enrolment ID: {$enrolmentId}\n";
echo "Request: " . json_encode($updateRequest, JSON_PRETTY_PRINT) . "\n";
$updateResponse = $controller->updateEnrolment($enrolmentId, $updateRequest);
echo "Response: " . json_encode($updateResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "=== 6. Check Access After Enrolment Shortened ===\n";
$accessRequest3 = [
    'student_id' => '1342',
    'course_id' => '5874',
    'content_id' => '8002', // Homework
    'access_time' => '2025-05-21T00:00:00Z'
];

echo "Request: " . json_encode($accessRequest3, JSON_PRETTY_PRINT) . "\n";
$accessResponse3 = $controller->checkAccess($accessRequest3);
echo "Response: " . json_encode($accessResponse3, JSON_PRETTY_PRINT) . "\n\n";

echo "=== 7. Error Handling - Missing Parameters ===\n";
$invalidRequest = [
    'student_id' => '1342',
    // Missing course_id, content_id, access_time
];

echo "Invalid Request: " . json_encode($invalidRequest, JSON_PRETTY_PRINT) . "\n";
$errorResponse = $controller->checkAccess($invalidRequest);
echo "Error Response: " . json_encode($errorResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "=== API Integration Benefits ===\n";
echo "✅ Clean separation between HTTP layer and business logic\n";
echo "✅ Consistent JSON responses with proper error handling\n";
echo "✅ Easy to integrate with any PHP framework (Symfony, Laravel, Slim)\n";
echo "✅ Follows REST API conventions\n";
echo "✅ Business logic remains testable and framework-agnostic\n";
echo "✅ Ready for authentication, validation middleware, etc.\n\n";

echo "=== Sample Framework Integration ===\n";
echo "// Symfony Controller Example:\n";
echo "class ApiController {\n";
echo "    #[Route('/api/content/access', methods: ['POST'])]\n";
echo "    public function checkAccess(Request \$request): JsonResponse {\n";
echo "        \$data = json_decode(\$request->getContent(), true);\n";
echo "        \$result = \$this->contentAccessController->checkAccess(\$data);\n";
echo "        return new JsonResponse(\$result);\n";
echo "    }\n";
echo "}\n\n";

echo "=== Demo Complete ===\n";
