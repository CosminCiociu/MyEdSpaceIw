# MyEdSpace LMS Backend

A simplified Learning Management System (LMS) backend that manages course content access control based on student enrolments and scheduling rules.

## Overview

This system implements the core business logic for determining whether a student can access specific course content at a given point in time. It's designed with API-first principles and focuses on:

- Object-oriented domain modeling
- Separation of concerns
- Testable and maintainable code
- Clear business rule implementation

## Architecture

### Domain Models

- **Student**: Represents a student in the system
- **Course**: Contains course information, scheduling, and content
- **ContentItem**: Abstract base for course content (Lesson, Homework, PrepMaterial)
  - **Lesson**: Content available from a specific scheduled datetime
  - **Homework**: Content available from course start
  - **PrepMaterial**: Content available from course start
- **Enrolment**: Links a student to a course for a specific time period

### Services

- **AccessControlService**: Core business logic for access control decisions
- **EnrolmentService**: Manages student enrolments
- **LMSService**: Main facade that coordinates access control and enrolment management

## Business Rules

A student can access content only if:

1. They are currently enrolled in the course (enrolment is active)
2. The course has already started
3. The specific content is available according to its rules:
   - **Lessons**: Only from their scheduled datetime
   - **Homework & Prep Materials**: From the course start onward

## Installation

```bash
composer install
```

## Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage
```

## API Usage

The system is designed to be consumed through REST API endpoints. See the API Controller implementation below for usage patterns.

## Test Scenarios

The implementation has been validated against the specific scenario from the requirements:

### A-Level Biology Course Example

- **Course**: "A-Level Biology" (13/05/2025 - 12/06/2025)
- **Content**:
  - Lesson: "Cell Structure" (15/05/2025 10:00)
  - Homework: "Label a Plant Cell"
  - Prep Material: "Biology Reading Guide"
- **Student**: Emma
- **Initial Enrolment**: 01/05/2025 → 30/05/2025

### Scenario Validation

1. ❌ **01/05/2025**: Prep Material access denied (course not started)
2. ✅ **13/05/2025**: Prep Material access granted
3. ✅ **15/05/2025 10:01**: Lesson access granted
4. **20/05/2025**: External system shortens enrolment to 20/05/2025
5. ❌ **21/05/2025**: Homework access denied (enrolment expired)
6. ❌ **30/05/2025**: Still denied
7. ❌ **10/06/2025**: Course running, but no enrolment

All scenarios are covered by comprehensive integration tests in `tests/Integration/EmmaScenarioTest.php`.

## API-Ready Design

The codebase is structured to make API integration straightforward:

- **LMSService** serves as the main entry point for API controllers
- Clear separation between domain models and business services
- Consistent return types (AccessResult) for API responses
- All business logic is encapsulated and testable

### API Controller Implementation

A complete `ContentAccessController` is included that demonstrates REST API integration:

**Endpoints:**

- `POST /api/content/access` - Check content access permissions
- `GET /api/courses/{id}/accessible-content` - Get all accessible content
- `POST /api/enrolments` - Create new enrolment
- `PUT /api/enrolments/{id}` - Update enrolment end date

**Example Usage:**

```bash
# Check if student can access content
curl -X POST /api/content/access \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": "1342",
    "course_id": "5874",
    "content_id": "8003",
    "access_time": "2025-05-13T00:00:00Z"
  }'

# Response:
{
  "allowed": true,
  "reason": "Access granted",
  "timestamp": "2025-05-13T00:00:00Z"
}
```

**Testing:**

````bash
# Run all tests (including API controller tests)
composer test

# Run API controller tests specifically
composer test tests/Unit/Controller/

# Run API demonstration
composer api-demo
```## Requirements

- PHP 8.1+
- PHPUnit 10+ (for development)

## Design Principles

- **Single Responsibility**: Each class has a clear, focused purpose
- **Open/Closed**: Content types can be extended without modifying existing code
- **Dependency Inversion**: Services depend on abstractions, not concretions
- **Domain-Driven Design**: Business concepts are clearly modeled
- **Test-Driven**: Comprehensive test coverage ensures correctness
````
