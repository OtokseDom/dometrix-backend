<?php

namespace App\Domain\Warehouses\Models;

use App\Domain\Organization\Models\Organization;
use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $table = 'warehouses';
    protected $keyType = 'string';

    protected $fillable = ['organization_id', 'code', 'name', 'type', 'location', 'is_active', 'manager_user_id', 'metadata'];
    protected $casts = ['is_active' => 'boolean', 'metadata' => 'array'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'manager_user_id');
    }
}
