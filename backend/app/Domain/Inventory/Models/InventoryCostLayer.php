<?php

namespace App\Domain\Inventory\Models;

use App\Traits\UsesUuid;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryCostLayer extends Model
{
    use HasFactory, UsesUuid, BelongsToOrganization;

    public $incrementing = false;
    protected $table = 'inventory_cost_layers';
    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'warehouse_id',
        'material_id',
        'batch_id',
        'source_movement_id',
        'original_qty',
        'remaining_qty',
        'unit_cost',
        'received_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'original_qty' => 'decimal:4',
        'remaining_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'received_at' => 'datetime',
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

    public function sourceMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'source_movement_id');
    }

    // Scopes
    public function scopeMaterial($query, string $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    public function scopeWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeWithRemaining($query)
    {
        return $query->where('remaining_qty', '>', 0);
    }

    public function scopeFifoOrder($query)
    {
        return $query->orderBy('received_at', 'asc');
    }

    // Methods
    public function getConsumedQty(): float
    {
        return (float) ($this->original_qty - $this->remaining_qty);
    }

    public function getConsumedValue(): float
    {
        return $this->getConsumedQty() * (float) $this->unit_cost;
    }

    public function getRemainingValue(): float
    {
        return (float) ($this->remaining_qty * $this->unit_cost);
    }
}
