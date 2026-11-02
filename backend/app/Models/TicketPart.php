<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class TicketPart extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'ticket_id', 'inventory_item_id', 'name', 'qty', 'unit_cost', 'unit_price', 'user_id'];

    protected $casts = ['unit_cost' => 'decimal:2', 'unit_price' => 'decimal:2'];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
