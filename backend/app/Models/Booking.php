<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class Booking extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 
        'name', 'phone', 'make', 'model', 'repair_type',
        'service_type', 'preferred_time', 'note', 'status', 'ticket_id',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
