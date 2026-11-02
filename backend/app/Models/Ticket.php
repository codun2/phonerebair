<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Concerns\BelongsToShop;

class Ticket extends Model
{
    use BelongsToShop;

    /** رمز تتبّع عام يُولَّد تلقائياً عند إنشاء أي تذكرة (حتى المُنشأة دون اتصال) */
    protected static function booted(): void
    {
        static::creating(function (Ticket $t) {
            if (empty($t->public_token)) {
                $t->public_token = Str::random(32);
            }
        });
    }

    protected $fillable = ['shop_id',
        'ticket_no', 'customer_id', 'customer_name', 'customer_phone', 'device_type',
        'make', 'model', 'imei', 'serial',
        'repair_type_id', 'repair_type_ids', 'description', 'condition', 'accessories', 'device_password',
        'estimated_cost', 'price', 'promised_at', 'warranty_months', 'status',
        'approved_at', 'approved_by', 'approval_via',
        'technician_id', 'created_by', 'delivered_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'condition' => 'array',
        'accessories' => 'array',
        'repair_type_ids' => 'array',
        'device_password' => 'encrypted',   // لا تُخزَّن أبداً كنص مكشوف
        'promised_at' => 'date',
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected $hidden = ['device_password']; // لا تخرج في أي JSON إلا عبر نقطة مخصصة

    /** دورة حياة الصيانة بالترتيب الطبيعي */
    public const STATUSES = [
        'draft', 'received', 'inspecting', 'awaiting_approval', 'waiting_part', 'in_repair',
        'testing', 'ready', 'delivered', 'rejected', 'unrepairable', 'cancelled',
    ];

    public const STATUS_LABELS = [
        'draft' => 'مسودة — استقبال سريع',
        'received' => 'مُستلم',
        'inspecting' => 'قيد الفحص',
        'awaiting_approval' => 'بانتظار الموافقة',
        'waiting_part' => 'بانتظار قطعة غيار',
        'in_repair' => 'قيد التصليح',
        'testing' => 'اختبار نهائي',
        'ready' => 'جاهز للاستلام',
        'delivered' => 'مُسلّم',
        'rejected' => 'رفض العميل',
        'unrepairable' => 'تعذّر التصليح',
        'cancelled' => 'ملغى',
    ];

    /** الحالات المفتوحة (جهاز ما زال داخل المحل) */
    public const OPEN_STATUSES = [
        'draft', 'received', 'inspecting', 'awaiting_approval', 'waiting_part', 'in_repair', 'testing', 'ready',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function repairType()
    {
        return $this->belongsTo(RepairType::class);
    }

    /** كل أعطال التذكرة (أسماء) — المصفوفة إن وُجدت، وإلا العطل المفرد */
    public function getFaultIdsAttribute(): array
    {
        $ids = $this->repair_type_ids ?: ($this->repair_type_id ? [$this->repair_type_id] : []);
        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function getRepairTypesAttribute(): array
    {
        $ids = $this->fault_ids;
        if (empty($ids)) return [];
        $names = RepairType::whereIn('id', $ids)->pluck('name', 'id');
        return collect($ids)->map(fn ($id) => $names[$id] ?? null)->filter()->values()->all();
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

    public function parts()
    {
        return $this->hasMany(TicketPart::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at');
    }

    public function getPaidAttribute(): float
    {
        return (float) ($this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount'));
    }

    public function getBalanceAttribute(): float
    {
        return round((float) ($this->price ?? 0) - $this->paid, 2);
    }

    public function getPartsCostAttribute(): float
    {
        $parts = $this->relationLoaded('parts') ? $this->parts : $this->parts()->get();
        return (float) $parts->sum(fn ($p) => $p->qty * (float) $p->unit_cost);
    }

    /** نهاية الضمان بعد التسليم */
    public function getWarrantyUntilAttribute(): ?string
    {
        if (! $this->delivered_at || ! $this->warranty_months) return null;
        return $this->delivered_at->copy()->addMonths((int) $this->warranty_months)->toDateString();
    }

    /** توليد رقم تذكرة تسلسلي مثل RX-10248 */
    public static function nextNumber(): string
    {
        $last = static::withoutGlobalScopes()->max('id') ?? 0;
        return 'RX-' . (10248 + $last);
    }
}
