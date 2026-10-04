<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case UniversityAdmin = 'university-admin';
    case DepartmentAdmin = 'department-admin';
    case Lecturer = 'lecturer';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::UniversityAdmin => 'University Admin',
            self::DepartmentAdmin => 'Department Admin',
            self::Lecturer => 'Lecturer',
            self::Student => 'Student',
        };
    }
}
