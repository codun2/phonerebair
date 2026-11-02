<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'name', 'customer_type', 'phone', 'alt_phone', 'email',
        'address', 'city', 'company', 'tax_number', 'notes'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
