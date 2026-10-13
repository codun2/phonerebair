<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'duration_days', 'description',
        'max_technicians', 'max_tickets_per_month', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }
}
