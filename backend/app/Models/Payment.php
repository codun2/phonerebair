<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'ticket_id', 'amount', 'method', 'note', 'user_id', 'paid_at'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];

    public const METHODS = ['cash', 'card', 'transfer', 'other'];

    public const METHOD_LABELS = [
        'cash' => 'نقدي',
        'card' => 'شبكة/بطاقة',
        'transfer' => 'تحويل',
        'other' => 'أخرى',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
