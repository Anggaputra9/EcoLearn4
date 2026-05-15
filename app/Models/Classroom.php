<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classroom extends Model
{
    protected $fillable = [
        'teacher_id', 'name', 'subject', 'code', 'description', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::where('code', $code)->exists());
        return $code;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_members')
            ->withTimestamps()->withPivot('joined_at');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }
}
