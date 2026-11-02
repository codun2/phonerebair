<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'name', 'phone', 'alt_phone', 'email', 'address', 'notes'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
