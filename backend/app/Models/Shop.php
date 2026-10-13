<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shop extends Model
{
    protected $fillable = [
        'name', 'slug', 'owner_name', 'phone', 'email', 'address', 'logo_path',
        'status', 'plan_id', 'trial_ends_at', 'subscription_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /** هل الاشتراك ساري؟ (فعّال + لم ينتهِ، أو ضمن الفترة التجريبية) */
    public function isSubscriptionActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->subscription_ends_at && $this->subscription_ends_at->isFuture()) {
            return true;
        }
        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) {
            return true;
        }
        // فعّال بدون تاريخ انتهاء محدد = مفتوح
        return ! $this->subscription_ends_at && ! $this->trial_ends_at;
    }

    public function accessState(): string
    {
        if ($this->status === 'pending') {
            return 'pending';     // بانتظار الموافقة
        }
        if ($this->status === 'suspended') {
            return 'suspended';   // موقوف
        }
        if (! $this->isSubscriptionActive()) {
            return 'expired';     // انتهى الاشتراك
        }
        return 'active';
    }

    public static function makeSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
