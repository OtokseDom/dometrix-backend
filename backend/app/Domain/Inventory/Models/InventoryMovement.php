<?php

namespace App\Domain\Inventory\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Organization\Models\Organization;
use App\Domain\Units\Models\Units;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryMovement extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $table = 'inventory_movements';
    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'warehouse_id',
        'material_id',
        'batch_id',
        'reference_type',
        'reference_id',
        'movement_type',
        'quantity',
        'unit_of_measure_id',
        'unit_cost',
        'total_cost',
        'running_balance',
        'direction',
        'performed_by',
        'remarks',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'running_balance' => 'decimal:4',
        'metadata' => 'array',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Units::class, 'unit_of_measure_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'performed_by');
    }

    public function costLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayer::class, 'source_movement_id');
    }

    // Scopes
    public function scopeOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeMaterial($query, string $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'IN');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'OUT');
    }

    public function scopeByType($query, string $movementType)
    {
        return $query->where('movement_type', $movementType);
    }

    public function scopeReference($query, string $referenceType, string $referenceId)
    {
        return $query->where('reference_type', $referenceType)->where('reference_id', $referenceId);
    }

    // Accessors
    public function getMovementTypeLabel(): string
    {
        return \App\Domain\Inventory\Enums\MovementType::from($this->movement_type)->label();
    }
}
