<?php

namespace App\Domain\Role\Models;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationUser;
use App\Domain\User\Models\User;
use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

//    Relationship
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'organization_user'
        )
            ->using(OrganizationUser::class)
            ->withPivot('organization_id')
            ->withTimestamps();
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'organization_user'
        )
            ->using(OrganizationUser::class)
            ->withPivot('user_id')
            ->withTimestamps();
    }
}
