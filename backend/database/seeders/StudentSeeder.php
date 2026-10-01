<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds demo students with accounts and an active program period.
 *
 * Deterministic and idempotent (`docs/database/seed-strategy.md`): accounts
 * upsert by email, profiles by `student_number`, and the program period is only
 * created when the student has no active one. Development-only password
 * `student@123`. Programs come from `ProgramSeeder`; missing ones are skipped.
 */
class StudentSeeder extends Seeder
{
    /**
     * @var list<array{number: string, first: string, last: string, gender: string, program: string}>
     */
    private const STUDENTS = [
        ['number' => 'ITC-2025-0001', 'first' => 'Sreyneang', 'last' => 'Chea', 'gender' => 'female', 'program' => 'BSCS'],
        ['number' => 'ITC-2025-0002', 'first' => 'Rithy', 'last' => 'Pov', 'gender' => 'male', 'program' => 'BSCS'],
        ['number' => 'ITC-2025-0003', 'first' => 'Malis', 'last' => 'Heng', 'gender' => 'female', 'program' => 'BSCS'],
        ['number' => 'ITC-2025-0004', 'first' => 'Visal', 'last' => 'Touch', 'gender' => 'male', 'program' => 'BEEE'],
        ['number' => 'ITC-2025-0005', 'first' => 'Kanha', 'last' => 'Meas', 'gender' => 'female', 'program' => 'BEEE'],
        ['number' => 'ITC-2025-0006', 'first' => 'Piseth', 'last' => 'Ouk', 'gender' => 'male', 'program' => 'BSMTH'],
        ['number' => 'ITC-2025-0007', 'first' => 'Chanthy', 'last' => 'Yim', 'gender' => 'female', 'program' => 'BSPHY'],
        ['number' => 'ITC-2025-0008', 'first' => 'Sambath', 'last' => 'Keo', 'gender' => 'male', 'program' => 'BAECO'],
        ['number' => 'ITC-2025-0009', 'first' => 'Davy', 'last' => 'Chhim', 'gender' => 'female', 'program' => 'BAENG'],
        ['number' => 'ITC-2025-0010', 'first' => 'Narith', 'last' => 'Som', 'gender' => 'male', 'program' => 'MSCS'],
    ];

    public function run(): void
    {
        $roleId = RoleModel::query()->where('slug', Role::Student->value)->value('id');

        if ($roleId === null) {
            return;
        }

        foreach (self::STUDENTS as $index => $definition) {
            $program = Program::query()->where('code', $definition['program'])->first();

            if ($program === null) {
                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => strtolower($definition['number']).'@student.educore.kh'],
                [
                    'name' => $definition['first'].' '.$definition['last'],
                    'password' => 'student@123',
                    'role_id' => $roleId,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $student = Student::query()->updateOrCreate(
                ['student_number' => $definition['number']],
                [
                    'user_id' => $user->id,
                    'first_name' => $definition['first'],
                    'last_name' => $definition['last'],
                    'gender' => $definition['gender'],
                    'date_of_birth' => sprintf('2005-%02d-15', $index + 1),
                    'enrollment_date' => '2025-09-01',
                    'status' => Student::STATUS_ACTIVE,
                ],
            );

            if (! $student->currentProgram()->exists()) {
                $student->programHistory()->create([
                    'program_id' => $program->id,
                    'started_on' => '2025-09-01',
                    'status' => StudentProgram::STATUS_ACTIVE,
                ]);
            }
        }
    }
}
