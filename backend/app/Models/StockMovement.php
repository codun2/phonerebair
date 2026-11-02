<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'inventory_item_id', 'type', 'qty', 'unit_cost', 'ticket_id', 'note', 'user_id'];

    public const TYPES = ['purchase', 'use', 'return', 'damage', 'adjust'];

    public const TYPE_LABELS = [
        'purchase' => 'شراء/توريد',
        'use' => 'استخدام في تذكرة',
        'return' => 'إرجاع',
        'damage' => 'تالف',
        'adjust' => 'تسوية جرد',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
