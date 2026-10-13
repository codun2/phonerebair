<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class Setting extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 'key', 'value'];

    public static function all_map(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
