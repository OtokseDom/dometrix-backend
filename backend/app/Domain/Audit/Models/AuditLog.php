<?php

namespace App\Domain\Audit\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\Organization\Models\Organization;
use App\Domain\User\Models\User;

class AuditLog extends Model
{
    use UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $table = 'audit_logs';
    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'user_id',
        'module',
        'entity_type',
        'entity_id',
        'action',
        'old_values',
        'new_values',
        'remarks',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by module
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Filter by organization and date range
     */
    public function scopeOrganizationDateRange($query, string $organizationId, ?\DateTime $fromDate = null, ?\DateTime $toDate = null)
    {
        $query->where('organization_id', $organizationId);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Scope: Filter by entity
     */
    public function scopeEntity($query, string $entityType, string $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }
}
