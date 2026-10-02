<?php

namespace App\Models;

// use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
