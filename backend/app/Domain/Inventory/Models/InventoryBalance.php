<?php

namespace App\Domain\Inventory\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Organization\Models\Organization;

class InventoryBalance extends Model
{
    use UsesUuid;

    public $incrementing = false;
    protected $table = 'inventory_balances';
    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'warehouse_id',
        'material_id',
        'batch_id',
        'on_hand_qty',
        'reserved_qty',
        'available_qty',
        'average_cost',
        'updated_at',
    ];

    protected $casts = [
        'on_hand_qty' => 'decimal:4',
        'reserved_qty' => 'decimal:4',
        'available_qty' => 'decimal:4',
        'average_cost' => 'decimal:4',
    ];

    public $timestamps = false;

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

    public function scopeWithStock($query)
    {
        return $query->where('on_hand_qty', '>', 0);
    }

    // Methods
    public function updateQuantities(float $onHand, float $reserved = 0): void
    {
        $this->update([
            'on_hand_qty' => $onHand,
            'reserved_qty' => $reserved,
            'available_qty' => $onHand - $reserved,
            'updated_at' => now(),
        ]);
    }

    public function getTotalValue(): float
    {
        return (float) ($this->on_hand_qty * ($this->average_cost ?? 0));
    }
}
