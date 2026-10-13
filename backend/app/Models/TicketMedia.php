<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class TicketMedia extends Model
{
    use BelongsToShop;

    protected $table = 'ticket_media';

    protected $fillable = ['shop_id', 'ticket_id', 'file_path', 'kind', 'sort_order'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
