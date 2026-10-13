<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class PriceRule extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 
        'make_id', 'car_model_id', 'repair_type_id',
        'price', 'duration_minutes', 'warranty_months',
    ];

    protected $casts = ['price' => 'decimal:2'];

    public function make()
    {
        return $this->belongsTo(Make::class);
    }

    public function model()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function repairType()
    {
        return $this->belongsTo(RepairType::class);
    }
}
