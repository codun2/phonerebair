<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class Technician extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'name', 'phone', 'specialty', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
