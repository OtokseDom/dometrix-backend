<?php

namespace App\Domain\Inventory\Models;

use App\Traits\UsesUuid;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryBatch extends Model
{
    use HasFactory, UsesUuid, SoftDeletes, BelongsToOrganization;

    public $incrementing = false;
    protected $table = 'inventory_batches';
    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'material_id',
        'warehouse_id',
        'batch_number',
        'manufactured_date',
        'received_date',
        'expiry_date',
        'received_qty',
        'remaining_qty',
        'unit_cost',
        'status',
        'metadata',
    ];

    protected $casts = [
        'manufactured_date' => 'date',
        'received_date' => 'date',
        'expiry_date' => 'date',
        'received_qty' => 'decimal:4',
        'remaining_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'metadata' => 'array',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'batch_id');
    }

    public function costLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayer::class, 'batch_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'EXPIRED');
    }

    public function scopeNotExpired($query)
    {
        return $query->whereIn('status', ['ACTIVE'])->where(function ($q) {
            $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
        });
    }

    public function scopeWithStock($query)
    {
        return $query->where('remaining_qty', '>', 0);
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED' || (
            $this->expiry_date && $this->expiry_date->isPast()
        );
    }

    public function getRemainingValue(): float
    {
        return (float) ($this->remaining_qty * $this->unit_cost);
    }

    public function markExpired(): void
    {
        $this->update(['status' => 'EXPIRED']);
    }
}
