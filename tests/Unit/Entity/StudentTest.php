<?php

declare(strict_types=1);

namespace MyEdSpace\LMS\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use MyEdSpace\LMS\Entity\Student;

class StudentTest extends TestCase
{
    public function testStudentCreation(): void
    {
        $student = new Student('1342', 'Emma Watson');

        $this->assertEquals('1342', $student->getId());
        $this->assertEquals('Emma Watson', $student->getName());
    }
}
