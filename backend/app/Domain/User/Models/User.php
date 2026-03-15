<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'email',
        'password',
        'role_id',
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

    // Relations
    public function organization()
    {
        return $this->belongsTo(\App\Domain\Organization\Models\Organization::class);
    }

    public function role()
    {
        return $this->belongsTo(\App\Domain\Role\Models\Role::class);
    }
}
