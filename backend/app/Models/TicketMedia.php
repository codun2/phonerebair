<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMedia extends Model
{
    protected $table = 'ticket_media';

    protected $fillable = ['ticket_id', 'file_path', 'kind', 'sort_order'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
