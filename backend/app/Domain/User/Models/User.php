<?php

namespace App\Domain\User\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use UsesUuid, HasApiTokens, Notifiable, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'password' => 'hashed',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
