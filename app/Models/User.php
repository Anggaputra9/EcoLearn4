<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'profile_photo_path',
        'theme',
        'otp_login_enabled',
    ];

    protected $appends = ['profile_photo_url'];

    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo_path) {
            return asset('storage/'.$this->profile_photo_path);
        }
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=10b981&color=ffffff&bold=true';
    }

    public function isAdmin(): bool   { return (int) $this->role_id === 1; }
    public function isTeacher(): bool { return (int) $this->role_id === 2; }
    public function isStudent(): bool { return (int) $this->role_id === 3; }

    /* === Relasi domain v0.3 === */
    public function classroomsTaught(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    public function classroomsJoined(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_members')
            ->withTimestamps()->withPivot('joined_at');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'otp_login_enabled'  => 'boolean',
        ];
    }
}
