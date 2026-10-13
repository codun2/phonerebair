<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToShop;

class Ticket extends Model
{
    use BelongsToShop;

    protected $fillable = ['shop_id', 
        'ticket_no', 'customer_name', 'customer_phone', 'make', 'model', 'imei',
        'repair_type_id', 'description', 'price', 'warranty_months', 'status',
        'technician_id', 'created_by', 'delivered_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'delivered_at' => 'datetime',
    ];

    /** حالات التذكرة بالترتيب */
    public const STATUSES = [
        'received', 'awaiting_approval', 'in_repair', 'ready', 'delivered', 'cancelled',
    ];

    public const STATUS_LABELS = [
        'received' => 'مُستلم',
        'awaiting_approval' => 'بانتظار الموافقة',
        'in_repair' => 'قيد التصليح',
        'ready' => 'جاهز للاستلام',
        'delivered' => 'مُسلّم',
        'cancelled' => 'ملغى',
    ];

    public function repairType()
    {
        return $this->belongsTo(RepairType::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function media()
    {
        return $this->hasMany(TicketMedia::class)->orderBy('sort_order');
    }

    public function events()
    {
        return $this->hasMany(TicketEvent::class)->latest();
    }

    /** توليد رقم تذكرة تسلسلي مثل RX-10248 */
    public static function nextNumber(): string
    {
        $last = static::max('id') ?? 0;
        return 'RX-' . (10248 + $last);
    }
}
