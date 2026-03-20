<?php

namespace App\Domain\Organization\Models;

use App\Domain\Role\Models\Role;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationUser extends Pivot
{
    public $timestamps = true;
    protected $table = 'organization_user';
    protected $fillable = [
        'organization_id',
        'user_id',
        'role_id'
    ];


//    Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
