<?php

namespace App\Models;

// use Database\Factories\UserFactory;
use App\Enums\Role as RoleSlug;
use App\Notifications\ResetPasswordLink;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'department_id',
        'phone',
        'avatar_key',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * The lecturer profile of a Lecturer-role account, if one exists.
     */
    public function lecturer()
    {
        return $this->hasOne(Lecturer::class);
    }

    /**
     * The student profile of a Student-role account, if one exists.
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /** Stored preferences, or the unsaved defaults (email on, no Telegram chat). */
    public function preferences(): NotificationPreference
    {
        return $this->notificationPreference ?? new NotificationPreference(['user_id' => $this->getKey()]);
    }

    /** Queued, EduCore-worded reset email instead of Laravel's default. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLink($token));
    }

    /** Telegram chat for `TelegramChannel`; null unless the user opted in and linked a chat. */
    public function routeNotificationForTelegram(): ?string
    {
        $preferences = $this->preferences();

        return $preferences->telegramReady() ? $preferences->telegram_chat_id : null;
    }

    public function isRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    /** The department a Department Admin administers (null for every other role). */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The department this user's data access is limited to: null = no unit
     * limit (every role except Department Admin); for a Department Admin their
     * department id, or 0 when none is assigned, which matches nothing (fail closed).
     */
    public function departmentScope(): ?int
    {
        return $this->isRole(RoleSlug::DepartmentAdmin->value) ? (int) ($this->department_id ?? 0) : null;
    }

    /**
     * The public or streamed URL to this user's avatar, or null if none is set.
     */
    public function avatarUrl(): ?string
    {
        if (empty($this->avatar_key)) {
            return null;
        }

        if (str_starts_with($this->avatar_key, 'http://') || str_starts_with($this->avatar_key, 'https://')) {
            return $this->avatar_key;
        }

        return route('avatar.show', ['user' => $this->getKey()]);
    }
}
