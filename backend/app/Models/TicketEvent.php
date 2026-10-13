<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class TicketEvent extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'ticket_id', 'status', 'note', 'user_id'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
