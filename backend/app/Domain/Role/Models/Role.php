<?php

namespace App\Domain\Role\Models;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationUser;
use App\Domain\User\Models\User;
use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['organization_id', 'name', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    //    Relationship
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'organization_user',
            'role_id',   // role_id on pivot
            'user_id'    // user_id on pivot
        )
            ->withPivot('organization_id')
            ->withTimestamps();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
