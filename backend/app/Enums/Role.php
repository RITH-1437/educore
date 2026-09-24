<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case UniversityAdmin = 'university-admin';
    case FacultyAdmin = 'faculty-admin';
    case Lecturer = 'lecturer';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::UniversityAdmin => 'University Admin',
            self::FacultyAdmin => 'Faculty / Department Admin',
            self::Lecturer => 'Lecturer',
            self::Student => 'Student',
        };
    }
}
