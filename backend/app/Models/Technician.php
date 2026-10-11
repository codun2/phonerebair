<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    protected $fillable = ['name', 'phone', 'specialty', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
