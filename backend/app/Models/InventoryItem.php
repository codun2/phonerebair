<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class InventoryItem extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'name', 'sku', 'compatible', 'qty', 'cost', 'low_threshold'];

    protected $casts = ['cost' => 'decimal:2'];

    public function getIsLowAttribute(): bool
    {
        return $this->qty <= $this->low_threshold;
    }
}
