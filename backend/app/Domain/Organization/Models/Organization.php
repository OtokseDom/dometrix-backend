<?php

namespace App\Domain\Organization\Models;

use App\Domain\User\Models\User;
use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use UsesUuid, SoftDeletes;

    protected $table = 'organizations';
    public $incrementing = false; // because we use UUID
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'code',
        'timezone',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationships
    // Users belonging to this organization via pivot
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    // public function products()
    // {
    //     return $this->hasMany(\App\Domain\Product\Models\Product::class);
    // }

    // public function materials()
    // {
    //     return $this->hasMany(\App\Domain\Material\Models\Material::class);
    // }
}
