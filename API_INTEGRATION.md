# API Integration Guide

This document demonstrates how the MyEdSpace LMS Backend is designed for API integration.

## Overview

The LMS backend follows a **layered architecture** that separates business logic from HTTP concerns:

```
HTTP Layer (Framework) → Controller → Service Layer → Domain Models
```

## Architecture Benefits

### ✅ **Clean Separation of Concerns**

- **Domain Models**: Pure business entities (Student, Course, Enrolment)
- **Service Layer**: Business logic (AccessControlService, LMSService)
- **Controller Layer**: HTTP request/response handling
- **Framework Layer**: Routing, middleware, authentication

### ✅ **Framework Agnostic**

The core business logic works with any PHP framework:

- Symfony
- Laravel
- Slim Framework
- Raw PHP
- API Platform

### ✅ **Testable Design**

- Business logic tested independently of HTTP
- Controller layer has focused tests
- Easy to mock dependencies

## API Endpoints

### 1. Check Content Access

```http
POST /api/content/access
Content-Type: application/json

{
  "student_id": "1342",
  "course_id": "5874",
  "content_id": "8003",
  "access_time": "2025-05-13T00:00:00Z"
}
```

**Response:**

```json
{
  "allowed": true,
  "reason": "Access granted",
  "timestamp": "2025-05-13T00:00:00Z",
  "student_id": "1342",
  "course_id": "5874",
  "content_id": "8003"
}
```

### 2. Get Accessible Content

```http
GET /api/courses/5874/accessible-content?student_id=1342&time=2025-05-15T10:01:00Z
```

**Response:**

```json
{
  "accessible_content": [
    {
      "id": "8001",
      "title": "Cell Structure",
      "type": "lesson"
    },
    {
      "id": "8002",
      "title": "Label a Plant Cell",
      "type": "homework"
    }
  ],
  "total_count": 2,
  "timestamp": "2025-05-15T10:01:00Z"
}
```

### 3. Create Enrolment

```http
POST /api/enrolments
Content-Type: application/json

{
  "student_id": "1342",
  "course_id": "5874",
  "start_date": "2025-05-01T00:00:00Z",
  "end_date": "2025-05-30T23:59:59Z"
}
```

### 4. Update Enrolment

```http
PUT /api/enrolments/enrol_123
Content-Type: application/json

{
  "end_date": "2025-05-20T23:59:59Z"
}
```

## Framework Integration Examples

### Symfony Integration

```php
#[Route('/api/content/access', methods: ['POST'])]
public function checkAccess(
    Request $request,
    ContentAccessController $controller
): JsonResponse {
    $data = json_decode($request->getContent(), true);
    $result = $controller->checkAccess($data);
    return new JsonResponse($result);
}
```

### Laravel Integration

```php
Route::post('/api/content/access', function (Request $request) {
    $controller = app(ContentAccessController::class);
    $result = $controller->checkAccess($request->all());
    return response()->json($result);
});
```

### Slim Framework Integration

```php
$app->post('/api/content/access', function (Request $request, Response $response) {
    $controller = $this->get(ContentAccessController::class);
    $data = json_decode($request->getBody()->getContents(), true);
    $result = $controller->checkAccess($data);
    return $response->withJson($result);
});
```

## Error Handling

The controller provides consistent error responses:

```json
{
  "error": true,
  "message": "Missing required field: course_id",
  "allowed": false
}
```

## Authentication & Authorization

The controller can be easily extended with middleware:

```php
// Symfony example with security
#[IsGranted('ROLE_STUDENT')]
#[Route('/api/content/access', methods: ['POST'])]
public function checkAccess(Request $request): JsonResponse
{
    // Ensure student can only check their own access
    $studentId = $this->getUser()->getId();
    $data = json_decode($request->getContent(), true);
    $data['student_id'] = $studentId; // Override with authenticated user

    $result = $this->controller->checkAccess($data);
    return new JsonResponse($result);
}
```

## Validation

Framework-specific validation can be added:

```php
// Laravel example with form request validation
class ContentAccessRequest extends FormRequest
{
    public function rules()
    {
        return [
            'student_id' => 'required|string',
            'course_id' => 'required|string',
            'content_id' => 'required|string',
            'access_time' => 'required|date_format:c'
        ];
    }
}
```

## Database Integration

In production, the mock data methods would be replaced with repository calls:

```php
private function getStudentById(string $studentId): Student
{
    // Replace mock with database query
    return $this->studentRepository->findById($studentId);
}

private function getCourseById(string $courseId): Course
{
    // Replace mock with database query
    $course = $this->courseRepository->findById($courseId);

    // Load course content from database
    $content = $this->contentRepository->findByCourseId($courseId);
    foreach ($content as $item) {
        $course->addContent($item);
    }

    return $course;
}
```

## Caching

The service layer supports caching strategies:

```php
public function getAccessibleContent(Student $student, Course $course, DateTimeImmutable $time): array
{
    $cacheKey = "accessible_content_{$student->getId()}_{$course->getId()}_{$time->format('Y-m-d-H')}";

    return $this->cache->remember($cacheKey, 3600, function() use ($student, $course, $time) {
        return $this->lmsService->getAccessibleContent($student, $course, $time);
    });
}
```

## API Documentation

The controller methods include comprehensive docblocks that can be used to generate OpenAPI/Swagger documentation automatically.

## Testing the API

```bash
# Run all tests (including controller tests)
composer test

# Run controller tests specifically
composer test tests/Unit/Controller/

# Run API demonstration
composer api-demo
```

This design ensures the LMS backend can be easily integrated into any existing system while maintaining clean, testable, and maintainable code.
