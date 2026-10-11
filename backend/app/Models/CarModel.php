<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    protected $fillable = ['make_id', 'name'];

    public function make()
    {
        return $this->belongsTo(Make::class);
    }
}
