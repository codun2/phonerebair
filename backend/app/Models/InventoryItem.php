<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class InventoryItem extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'name', 'sku', 'compatible', 'qty', 'cost', 'sale_price', 'supplier', 'supplier_id', 'low_threshold'];

    public function supplierRef()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    protected $casts = ['cost' => 'decimal:2', 'sale_price' => 'decimal:2'];

    public function getIsLowAttribute(): bool
    {
        return $this->qty <= $this->low_threshold;
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'inventory_item_id')->latest();
    }

    /** تحريك المخزون: المصدر الوحيد لتغيير الكمية + يسجّل الحركة */
    public function move(string $type, int $qty, array $extra = []): StockMovement
    {
        $this->increment('qty', $qty);
        return $this->movements()->create([
            'type' => $type,
            'qty' => $qty,
            'unit_cost' => $extra['unit_cost'] ?? $this->cost,
            'ticket_id' => $extra['ticket_id'] ?? null,
            'note' => $extra['note'] ?? null,
            'user_id' => $extra['user_id'] ?? null,
        ]);
    }
}
