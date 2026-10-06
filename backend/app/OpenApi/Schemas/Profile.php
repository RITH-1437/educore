<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** The signed-in user's profile portal (`docs/45_Profile-Portal-Report.md`). */
#[OA\Schema(
    schema: 'Profile',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'role', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: true),
            new OA\Property(property: 'name', type: 'string', nullable: true),
            new OA\Property(property: 'slug', type: 'string', nullable: true),
        ]),
        new OA\Property(property: 'department', type: 'object', nullable: true, description: 'The assigned department, or the student\'s program / lecturer\'s department.', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'code', type: 'string'),
        ]),
        new OA\Property(property: 'avatar_key', type: 'string', nullable: true, description: 'A storage key for an uploaded avatar, or the external http(s) image URL.'),
        new OA\Property(property: 'avatar_url', type: 'string', nullable: true, description: 'Where to load the avatar: `/users/{id}/avatar` for an upload, the external URL itself otherwise.'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'last_login_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'student', type: 'object', nullable: true, description: 'Students with a linked profile only.', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'student_number', type: 'string'),
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name', type: 'string'),
            new OA\Property(property: 'gender', type: 'string', nullable: true),
            new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'address', type: 'string', nullable: true),
            new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true),
            new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true),
            new OA\Property(property: 'national_id', type: 'string', nullable: true),
            new OA\Property(property: 'enrollment_date', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'status', type: 'string'),
            new OA\Property(property: 'program', type: 'object', nullable: true, properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
            ]),
            new OA\Property(property: 'academic_summary', type: 'object', properties: [
                new OA\Property(property: 'cumulative_gpa', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'earned_credits', type: 'number'),
                new OA\Property(property: 'attempted_credits', type: 'number'),
                new OA\Property(property: 'current_courses_count', type: 'integer'),
            ]),
        ]),
        new OA\Property(property: 'lecturer', type: 'object', nullable: true, description: 'Lecturers with a linked profile only.', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'staff_number', type: 'string'),
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name', type: 'string'),
            new OA\Property(property: 'title', type: 'string', nullable: true),
            new OA\Property(property: 'position', type: 'string', nullable: true),
            new OA\Property(property: 'specialization', type: 'string', nullable: true),
            new OA\Property(property: 'employment_type', type: 'string', nullable: true),
            new OA\Property(property: 'is_active', type: 'boolean'),
            new OA\Property(property: 'teaching_summary', type: 'object', properties: [
                new OA\Property(property: 'active_sections_count', type: 'integer'),
            ]),
        ]),
    ]
)]
class Profile {}
