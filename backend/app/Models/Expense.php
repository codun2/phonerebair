<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'title', 'category', 'amount', 'spent_at', 'note', 'user_id'];

    protected $casts = ['amount' => 'decimal:2', 'spent_at' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
