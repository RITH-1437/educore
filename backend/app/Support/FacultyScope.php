<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Id subqueries for "what belongs to faculty F" (`docs/32_Faculty-Admin-Scoping-Report.md`).
 * Every unit-owned model's `inFaculty` scope is built from these, so the
 * ownership rules live in one place:
 *
 * | Record            | Belongs to F when…                                         |
 * |-------------------|------------------------------------------------------------|
 * | department        | `faculty_id = F`                                           |
 * | program / course / lecturer | its department is in F                           |
 * | course offering / section   | its course is in F                               |
 * | student           | any of their program records is a program of F             |
 * | enrollment        | its section's course is in F, or its student is in F       |
 * | document request / internship | its student is in F                            |
 *
 * Plain query-builder subqueries (no soft-delete filter): ownership does not
 * change when a parent is archived.
 */
class FacultyScope
{
    public static function departmentIds(int $facultyId): Builder
    {
        return DB::table('departments')->select('id')->where('faculty_id', $facultyId);
    }

    public static function programIds(int $facultyId): Builder
    {
        return DB::table('programs')->select('id')->whereIn('department_id', self::departmentIds($facultyId));
    }

    public static function courseIds(int $facultyId): Builder
    {
        return DB::table('courses')->select('id')->whereIn('department_id', self::departmentIds($facultyId));
    }

    public static function offeringIds(int $facultyId): Builder
    {
        return DB::table('course_offerings')->select('id')->whereIn('course_id', self::courseIds($facultyId));
    }

    public static function sectionIds(int $facultyId): Builder
    {
        return DB::table('sections')->select('id')->whereIn('course_offering_id', self::offeringIds($facultyId));
    }

    public static function studentIds(int $facultyId): Builder
    {
        return DB::table('student_programs')->select('student_id')->whereIn('program_id', self::programIds($facultyId));
    }
}
