<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Id subqueries for "what belongs to department D"
 * (`docs/39_Department-Only-Structure-Report.md`, formerly faculty scoping in
 * report 32). Every unit-owned model's `inDepartment` scope is built from
 * these, so the ownership rules live in one place:
 *
 * | Record                        | Belongs to D when…                                 |
 * |-------------------------------|----------------------------------------------------|
 * | department                    | it is D                                            |
 * | program / course / lecturer   | `department_id = D`                                |
 * | course offering / section     | its course is in D                                 |
 * | student                       | any of their program records is a program of D     |
 * | enrollment                    | its section's course is in D, or its student is in D |
 * | document request / internship | its student is in D                                |
 *
 * Plain query-builder subqueries (no soft-delete filter): ownership does not
 * change when a parent is archived.
 */
class DepartmentScope
{
    public static function programIds(int $departmentId): Builder
    {
        return DB::table('programs')->select('id')->where('department_id', $departmentId);
    }

    public static function courseIds(int $departmentId): Builder
    {
        return DB::table('courses')->select('id')->where('department_id', $departmentId);
    }

    public static function offeringIds(int $departmentId): Builder
    {
        return DB::table('course_offerings')->select('id')->whereIn('course_id', self::courseIds($departmentId));
    }

    public static function sectionIds(int $departmentId): Builder
    {
        return DB::table('sections')->select('id')->whereIn('course_offering_id', self::offeringIds($departmentId));
    }

    public static function studentIds(int $departmentId): Builder
    {
        return DB::table('student_programs')->select('student_id')->whereIn('program_id', self::programIds($departmentId));
    }
}
