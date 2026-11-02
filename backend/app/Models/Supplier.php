<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id', 'name', 'contact_name', 'phone', 'alt_phone', 'email', 'whatsapp', 'website',
        'address', 'city', 'country', 'tax_number', 'payment_terms', 'bank_details',
        'categories', 'notes', 'active',
    ];

    protected $casts = ['active' => 'boolean'];

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }
}
