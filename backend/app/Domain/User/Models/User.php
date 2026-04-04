<?php

namespace App\Domain\User\Models;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationUser;
use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, UsesUuid, HasApiTokens, Notifiable, SoftDeletes;

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

    //    Relationship
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'organization_user',
            'user_id',
            'organization_id'
        )
            ->withPivot('role_id', 'status')
            ->withTimestamps();
    }
}
