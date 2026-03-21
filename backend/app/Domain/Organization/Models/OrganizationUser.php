<?php

namespace App\Domain\Organization\Models;

use App\Domain\Role\Models\Role;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationUser extends Pivot
{
    public $timestamps = true;
    public $incrementing = false;
    protected $table = 'organization_user';
    protected $fillable = [
        'organization_id',
        'user_id',
        'role_id'
    ];


    //    Relationships

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
