<?php

namespace App\Domain\Organization\Models;

use App\Domain\Role\Models\Role;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationUser extends Model
{
    public $timestamps = true;
    public $incrementing = false;
    protected $table = 'organization_user';
    protected $fillable = [
        'organization_id',
        'user_id',
        'role_id',
        'status'
    ];


    //    Relationships

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
