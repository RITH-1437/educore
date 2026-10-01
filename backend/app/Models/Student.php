<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A student's academic profile. Credentials live on `users` (role `student`);
 * this row is the single source of truth for the academic profile
 * (`skills/student-management/SKILL.md` §2).
 *
 * The program is not a column: `student_programs` keeps the history, with
 * exactly one `active` row (partial unique index `uq_student_programs_active`).
 *
 * @property int $id
 * @property int $user_id
 * @property string $student_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $gender
 * @property Carbon|null $date_of_birth
 * @property string|null $address
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $national_id
 * @property Carbon|null $enrollment_date
 * @property string $status
 */
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_GRADUATED = 'graduated';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_SUSPENDED,
        self::STATUS_GRADUATED, self::STATUS_WITHDRAWN,
    ];

    /**
     * Allowed status transitions. `graduated` and `withdrawn` are final;
     * re-admission is a later feature.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_ACTIVE => [self::STATUS_INACTIVE, self::STATUS_SUSPENDED, self::STATUS_GRADUATED, self::STATUS_WITHDRAWN],
        self::STATUS_INACTIVE => [self::STATUS_ACTIVE, self::STATUS_WITHDRAWN],
        self::STATUS_SUSPENDED => [self::STATUS_ACTIVE, self::STATUS_WITHDRAWN],
        self::STATUS_GRADUATED => [],
        self::STATUS_WITHDRAWN => [],
    ];

    /** Statuses whose account may still sign in (graduates keep document access). */
    public const SIGN_IN_STATUSES = [self::STATUS_ACTIVE, self::STATUS_GRADUATED];

    public const GENDERS = ['male', 'female', 'other'];

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'national_id',
        'enrollment_date',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Program history, newest first.
     */
    public function programHistory(): HasMany
    {
        return $this->hasMany(StudentProgram::class)->orderByDesc('started_on')->orderByDesc('id');
    }

    /**
     * The single active program assignment, if any.
     */
    public function currentProgram(): HasOne
    {
        return $this->hasOne(StudentProgram::class)->where('status', StudentProgram::STATUS_ACTIVE);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('student_number', 'ilike', "%{$search}%")
                ->orWhere('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
                ->orWhere('national_id', 'ilike', "%{$search}%")
                ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'ilike', "%{$search}%"));
        });
    }
}
