<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['name', 'sku', 'compatible', 'qty', 'cost', 'low_threshold'];

    protected $casts = ['cost' => 'decimal:2'];

    public function getIsLowAttribute(): bool
    {
        return $this->qty <= $this->low_threshold;
    }
}
