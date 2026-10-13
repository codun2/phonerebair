<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class RepairType extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'name', 'slug', 'icon', 'sort_order', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function priceRules()
    {
        return $this->hasMany(PriceRule::class);
    }
}
