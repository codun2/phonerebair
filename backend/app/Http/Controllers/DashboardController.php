<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\TicketPart;
use App\Models\CarModel;
use App\Models\InventoryItem;
use App\Models\Make;
use App\Models\PriceRule;
use App\Models\RepairType;
use App\Models\Setting;
use App\Models\Technician;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** لوحة تحكم المحل — تتطلب تسجيل دخول */
class DashboardController extends Controller
{
    public function stats()
    {
        $open = Ticket::whereNotIn('status', ['delivered', 'cancelled'])->count();
        $todayRevenue = Ticket::whereDate('delivered_at', today())->sum('price');

        return response()->json([
            'open_tickets' => $open,
            'in_repair' => Ticket::where('status', 'in_repair')->count(),
            'ready' => Ticket::where('status', 'ready')->count(),
            'today_revenue' => (float) $todayRevenue,
            'low_stock' => InventoryItem::whereColumn('qty', '<=', 'low_threshold')->count(),
            'month_revenue' => (float) Payment::whereBetween('paid_at', [now()->startOfMonth(), now()])->sum('amount'),
            'debts' => (float) (Ticket::whereNotIn('status', ['cancelled', 'rejected', 'unrepairable'])->sum('price') - Payment::sum('amount')),
            'overdue' => Ticket::whereIn('status', Ticket::OPEN_STATUSES)->whereDate('promised_at', '<', today())->count(),
            'new_bookings' => Booking::where('status', 'new')->count(),
            'technicians_active' => Technician::where('active', true)->count(),
            'recent' => Ticket::with(['repairType:id,name', 'technician:id,name'])
                ->latest()->limit(6)->get()->map(fn ($t) => $this->ticketRow($t)),
            'tech_perf' => Technician::where('active', true)->withCount([
                'tickets as done_today' => fn ($q) => $q->whereDate('updated_at', today())
                    ->whereIn('status', ['ready', 'delivered']),
            ])->get(['id', 'name', 'specialty', 'active']),
        ]);
    }

    // ================= التذاكر =================
    public function tickets(Request $request)
    {
        $q = Ticket::with(['repairType:id,name', 'technician:id,name'])
            ->withSum('payments as paid_sum', 'amount')->latest();
        if ($request->filled('status')) $q->where('status', $request->input('status'));
        if ($request->filled('customer_id')) $q->where('customer_id', $request->input('customer_id'));
        if ($request->boolean('due')) $q->whereNotNull('price'); // الديون تُرشَّح بعد الحساب
        if ($s = $request->input('search')) {
            $q->where(fn ($w) => $w->where('ticket_no', 'like', "%$s%")
                ->orWhere('customer_name', 'like', "%$s%")
                ->orWhere('customer_phone', 'like', "%$s%")
                ->orWhere('imei', 'like', "%$s%")
                ->orWhere('serial', 'like', "%$s%"));
        }

        return response()->json([
            'tickets' => $q->get()->map(fn ($t) => $this->ticketRow($t)),
            'counts' => Ticket::select('status', DB::raw('count(*) as c'))
                ->groupBy('status')->pluck('c', 'status'),
            'statuses' => $this->statusList(),
        ]);
    }

    public function ticket(Ticket $ticket)
    {
        $ticket->load(['repairType', 'technician', 'media', 'events.user:id,name',
            'customer', 'parts.item:id,name,qty', 'payments.user:id,name']);
        $ticket->append(['paid', 'balance', 'parts_cost', 'warranty_until', 'fault_ids', 'repair_types']);
        $ticket->has_password = filled($ticket->getRawOriginal('device_password'));

        return response()->json([
            'ticket' => $ticket,
            'status_label' => Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status,
            'statuses' => $this->statusList(),
            'payment_methods' => collect(Payment::METHODS)->map(fn ($m) => ['key' => $m, 'label' => Payment::METHOD_LABELS[$m]])->values(),
        ]);
    }

    /** كلمة مرور الجهاز — تُطلب عند الحاجة فقط ولا تخرج مع باقي البيانات */
    public function ticketPassword(Ticket $ticket)
    {
        return response()->json(['password' => $ticket->device_password]);
    }

    /** توثيق موافقة/رفض العميل على التكلفة */
    public function ticketApproval(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'approved' => 'required|boolean',
            'via' => 'required|in:in_person,phone,whatsapp',
            'price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $via = ['in_person' => 'حضورياً', 'phone' => 'هاتفياً', 'whatsapp' => 'واتساب'][$data['via']];
        $note = $data['note'] ?? null;
        if ($data['approved']) {
            $ticket->update([
                'approved_at' => now(), 'approved_by' => $request->user()->id, 'approval_via' => $data['via'],
                'price' => $data['price'] ?? $ticket->price ?? $ticket->estimated_cost,
                'status' => in_array($ticket->status, ['received', 'inspecting', 'awaiting_approval']) ? 'in_repair' : $ticket->status,
            ]);
            $this->logEvent($ticket, $ticket->status, 'وافق العميل على التكلفة (' . $via . ')' . ($note ? ' — ' . $note : ''), $request->user()->id);
        } else {
            $ticket->update(['status' => 'rejected', 'approval_via' => $data['via']]);
            $this->logEvent($ticket, 'rejected', 'رفض العميل التكلفة (' . $via . ')' . ($note ? ' — ' . $note : ''), $request->user()->id);
        }

        return response()->json(['message' => 'تم التوثيق', 'ticket' => $ticket->fresh()]);
    }

    // ================= قطع الغيار المستخدمة بالتذكرة =================

    /** إضافة قطعة للتذكرة: تخصم من المخزون وتسجَّل الحركة مربوطة بالتذكرة */
    public function addTicketPart(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'inventory_item_id' => 'required|integer',
            'qty' => 'required|integer|min:1|max:1000',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        if ($item->qty < $data['qty']) {
            return response()->json(['message' => "الكمية غير كافية — المتوفر {$item->qty} فقط. سجّل توريداً أو حوّل التذكرة إلى «بانتظار قطعة غيار»."], 422);
        }

        $part = DB::transaction(function () use ($data, $item, $ticket, $request) {
            $item->move('use', -$data['qty'], [
                'ticket_id' => $ticket->id,
                'note' => 'تذكرة ' . $ticket->ticket_no,
                'user_id' => $request->user()->id,
            ]);

            return $ticket->parts()->create([
                'inventory_item_id' => $item->id,
                'name' => $item->name,
                'qty' => $data['qty'],
                'unit_cost' => $item->cost ?? 0,
                'unit_price' => $data['unit_price'] ?? $item->sale_price ?? $item->cost ?? 0,
                'user_id' => $request->user()->id,
            ]);
        });

        return response()->json($part->load('item:id,name,qty'), 201);
    }

    /** إزالة قطعة من التذكرة: تُرجَع للمخزون */
    public function removeTicketPart(Request $request, Ticket $ticket, TicketPart $part)
    {
        abort_unless($part->ticket_id === $ticket->id, 404);
        DB::transaction(function () use ($part, $ticket, $request) {
            if ($part->inventory_item_id && $part->item) {
                $part->item->move('return', $part->qty, [
                    'ticket_id' => $ticket->id,
                    'note' => 'إرجاع من تذكرة ' . $ticket->ticket_no,
                    'user_id' => $request->user()->id,
                ]);
            }
            $part->delete();
        });

        return response()->json(['message' => 'أُعيدت القطعة للمخزون']);
    }

    // ================= المدفوعات =================

    public function addPayment(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:' . implode(',', Payment::METHODS),
            'note' => 'nullable|string|max:300',
        ]);

        $payment = $ticket->payments()->create($data + [
            'user_id' => $request->user()->id,
            'paid_at' => now(),
        ]);
        $this->logEvent($ticket, $ticket->status, 'دفعة ' . number_format($data['amount'], 2) . ' (' . Payment::METHOD_LABELS[$data['method']] . ')', $request->user()->id);

        return response()->json(['payment' => $payment, 'paid' => $ticket->paid, 'balance' => $ticket->balance], 201);
    }

    public function removePayment(Request $request, Ticket $ticket, Payment $payment)
    {
        abort_unless($payment->ticket_id === $ticket->id, 404);
        $payment->delete();
        $this->logEvent($ticket, $ticket->status, 'حذف دفعة ' . number_format((float) $payment->amount, 2), $request->user()->id);

        return response()->json(['message' => 'تم حذف الدفعة', 'paid' => $ticket->paid, 'balance' => $ticket->balance]);
    }

    public function storeTicket(Request $request)
    {
        $data = $this->validatedTicket($request);
        $data['customer_id'] = $this->resolveCustomer($data);
        $data['ticket_no'] = Ticket::nextNumber();
        $data['created_by'] = $request->user()->id;

        $ticket = Ticket::create($data);
        $this->syncMedia($ticket, $request->input('media', []));
        $this->logEvent($ticket, $ticket->status, 'تم فتح التذكرة', $request->user()->id);

        // لو من حجز، اربطه
        if ($request->filled('booking_id')) {
            Booking::where('id', $request->input('booking_id'))
                ->update(['status' => 'converted', 'ticket_id' => $ticket->id]);
        }

        return response()->json($ticket->load(['repairType', 'technician', 'media', 'events']), 201);
    }

    public function updateTicket(Request $request, Ticket $ticket)
    {
        $data = $this->validatedTicket($request);
        $data['customer_id'] = $this->resolveCustomer($data, $ticket);
        $statusChanged = isset($data['status']) && $data['status'] !== $ticket->status;

        if ($statusChanged && $data['status'] === 'delivered') {
            $data['delivered_at'] = now();
        }

        $ticket->update($data);
        if ($request->has('media')) $this->syncMedia($ticket, $request->input('media', []));

        if ($statusChanged) {
            $this->logEvent($ticket, $data['status'], $request->input('note')
                ?? (Ticket::STATUS_LABELS[$data['status']] ?? null), $request->user()->id);
        }

        return response()->json($ticket->load(['repairType', 'technician', 'media', 'events']));
    }

    /** تعيين/تغيير الفني المسؤول فقط (سريع من قائمة التذاكر) */
    public function assignTechnician(Request $request, Ticket $ticket)
    {
        $data = $request->validate(['technician_id' => 'nullable|exists:technicians,id']);
        $ticket->update(['technician_id' => $data['technician_id']]);

        return response()->json([
            'message' => 'تم تعيين الفني',
            'technician' => $ticket->technician()->first(['id', 'name']),
        ]);
    }

    /** تغيير الحالة فقط (سريع) */
    public function ticketStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Ticket::STATUSES),
            'note' => 'nullable|string|max:500',
        ]);
        if ($data['status'] === 'delivered') $ticket->delivered_at = now();
        $ticket->status = $data['status'];
        $ticket->save();
        $this->logEvent($ticket, $data['status'], $data['note'] ?? (Ticket::STATUS_LABELS[$data['status']] ?? null), $request->user()->id);

        return response()->json(['message' => 'تم تحديث الحالة', 'ticket' => $ticket]);
    }

    public function destroyTicket(Ticket $ticket)
    {
        $ticket->media()->delete();
        $ticket->events()->delete();
        $ticket->delete();

        return response()->json(['message' => 'تم حذف التذكرة']);
    }

    // ================= المخزون =================
    public function inventory()
    {
        return response()->json(InventoryItem::with('supplierRef:id,name')->orderBy('name')->get()
            ->map(function ($i) {
                $i->append('is_low');
                // اسم المورد: من الملف المربوط، وإلا النص القديم
                $i->supplier_name = $i->supplierRef?->name ?: $i->supplier;
                return $i;
            }));
    }

    public function itemMovements(InventoryItem $item)
    {
        return response()->json([
            'item' => $item->append('is_low'),
            'movements' => $item->movements()->with(['user:id,name', 'ticket:id,ticket_no'])->limit(100)->get()
                ->map(fn ($m) => [
                    'id' => $m->id, 'type' => $m->type,
                    'type_label' => StockMovement::TYPE_LABELS[$m->type] ?? $m->type,
                    'qty' => $m->qty, 'unit_cost' => $m->unit_cost, 'note' => $m->note,
                    'ticket_no' => $m->ticket?->ticket_no, 'ticket_id' => $m->ticket_id,
                    'user' => $m->user?->name, 'created_at' => $m->created_at->format('Y-m-d H:i'),
                ]),
        ]);
    }

    /** حركة يدوية: توريد/إرجاع/تالف/تسوية جرد */
    public function storeMovement(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'type' => 'required|in:purchase,return,damage,adjust',
            'qty' => 'required|integer|min:1|max:100000',
            'unit_cost' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:300',
        ]);

        // التسوية ترسل الكمية الفعلية الجديدة؛ الباقي كميات تُضاف أو تُخصم
        $delta = match ($data['type']) {
            'purchase', 'return' => $data['qty'],
            'damage' => -$data['qty'],
            'adjust' => $data['qty'] - $item->qty,
        };
        if ($delta < 0 && $item->qty + $delta < 0) {
            return response()->json(['message' => "الكمية غير كافية — المتوفر {$item->qty} فقط"], 422);
        }

        $item->move($data['type'], $delta, [
            'unit_cost' => $data['unit_cost'] ?? null,
            'note' => $data['note'] ?? null,
            'user_id' => $request->user()->id,
        ]);
        // التوريد بتكلفة جديدة يحدّث تكلفة القطعة
        if ($data['type'] === 'purchase' && isset($data['unit_cost'])) {
            $item->update(['cost' => $data['unit_cost']]);
        }

        return response()->json(['message' => 'سُجّلت الحركة', 'item' => $item->fresh()->append('is_low')], 201);
    }

    public function storeItem(Request $request)
    {
        $item = InventoryItem::create($this->validatedItem($request));
        if ($item->qty > 0) {
            $item->movements()->create(['type' => 'purchase', 'qty' => $item->qty,
                'unit_cost' => $item->cost, 'note' => 'رصيد افتتاحي', 'user_id' => $request->user()->id]);
        }

        return response()->json($item, 201);
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        // الكمية لا تتغير من هنا — فقط عبر الحركات حتى يبقى السجل صادقاً
        $item->update(collect($this->validatedItem($request))->except('qty')->all());

        return response()->json(['message' => 'تم التحديث', 'item' => $item->fresh()->append('is_low')]);
    }

    public function destroyItem(InventoryItem $item)
    {
        $item->delete();

        return response()->json(['message' => 'تم حذف القطعة']);
    }

    // ================= الفنيون =================
    public function technicians()
    {
        return response()->json(
            Technician::withCount([
                'tickets as open' => fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled']),
                'tickets as month' => fn ($q) => $q->whereMonth('created_at', now()->month),
            ])->orderBy('name')->get()
        );
    }

    public function storeTechnician(Request $request)
    {
        return response()->json(Technician::create($this->validatedTech($request)), 201);
    }

    public function updateTechnician(Request $request, Technician $technician)
    {
        $technician->update($this->validatedTech($request));

        return response()->json(['message' => 'تم التحديث', 'technician' => $technician]);
    }

    public function destroyTechnician(Technician $technician)
    {
        $technician->delete();

        return response()->json(['message' => 'تم حذف الفني']);
    }

    // ================= الحجوزات =================
    public function bookings(Request $request)
    {
        $q = Booking::latest();
        if ($request->filled('status')) $q->where('status', $request->input('status'));

        return response()->json($q->get());
    }

    public function bookingStatus(Request $request, Booking $booking)
    {
        $data = $request->validate(['status' => 'required|in:new,contacted,converted,closed']);
        $booking->update($data);

        return response()->json(['message' => 'تم التحديث', 'booking' => $booking]);
    }

    // ================= أنواع الأعطال =================
    public function repairTypes()
    {
        return response()->json(RepairType::orderBy('sort_order')->get());
    }

    public function storeRepairType(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'icon' => 'nullable|string|max:60',
        ]);
        $data['sort_order'] = (RepairType::max('sort_order') ?? 0) + 1;

        return response()->json(RepairType::create($data), 201);
    }

    public function updateRepairType(Request $request, RepairType $repairType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'icon' => 'nullable|string|max:60',
            'active' => 'boolean',
        ]);
        $repairType->update($data);

        return response()->json(['message' => 'تم التحديث', 'repair_type' => $repairType]);
    }

    public function destroyRepairType(RepairType $repairType)
    {
        $repairType->delete();

        return response()->json(['message' => 'تم الحذف']);
    }

    // ================= جدول الأسعار =================
    public function priceRules()
    {
        return response()->json(
            PriceRule::with(['make:id,name', 'model:id,name', 'repairType:id,name'])
                ->latest()->get()
        );
    }

    public function storePriceRule(Request $request)
    {
        $data = $this->validatedRule($request);

        $rule = PriceRule::updateOrCreate(
            [
                'make_id' => $data['make_id'],
                'car_model_id' => $data['car_model_id'] ?? null,
                'repair_type_id' => $data['repair_type_id'],
            ],
            $data,
        );

        return response()->json($rule->load(['make', 'model', 'repairType']), 201);
    }

    public function updatePriceRule(Request $request, PriceRule $priceRule)
    {
        $priceRule->update($this->validatedRule($request));

        return response()->json(['message' => 'تم التحديث', 'rule' => $priceRule]);
    }

    public function destroyPriceRule(PriceRule $priceRule)
    {
        $priceRule->delete();

        return response()->json(['message' => 'تم الحذف']);
    }

    // ================= الكتالوج (ماركات/موديلات) =================
    public function catalog()
    {
        return response()->json(Make::with('models')->orderBy('name')->get());
    }

    public function storeMake(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60|unique:makes,name']);

        return response()->json(Make::create($data)->load('models'), 201);
    }

    public function updateMake(Request $request, Make $make)
    {
        $data = $request->validate(['name' => 'required|string|max:60|unique:makes,name,' . $make->id]);
        $make->update($data);

        return response()->json(['message' => 'تم التعديل', 'make' => $make]);
    }

    public function deleteMake(Make $make)
    {
        $make->delete();

        return response()->json(['message' => 'تم حذف الماركة وموديلاتها']);
    }

    public function storeModel(Request $request, Make $make)
    {
        $data = $request->validate(['name' => 'required|string|max:80']);

        return response()->json($make->models()->firstOrCreate(['name' => $data['name']]), 201);
    }

    public function updateModel(Request $request, CarModel $carModel)
    {
        $data = $request->validate(['name' => 'required|string|max:80']);
        $carModel->update($data);

        return response()->json(['message' => 'تم التعديل', 'model' => $carModel]);
    }

    public function deleteModel(CarModel $carModel)
    {
        $carModel->delete();

        return response()->json(['message' => 'تم حذف الموديل']);
    }

    // ================= الإعدادات =================
    public const SETTING_KEYS = [
        'shop_name', 'phone', 'whatsapp', 'address', 'hours', 'about',
        'hero_badge', 'hero_title', 'hero_subtitle',
        'cta_title', 'cta_text', 'footer_text',
        // المظهر: اللون الأساسي + إظهار/إخفاء أقسام الصفحة الرئيسية
        'theme_primary',
        'show_quote', 'show_services', 'show_why', 'show_track_cta',
        // التسعير: هامش الربح الافتراضي للمحل (نسبة مئوية)
        'profit_margin',
    ];

    public function getSettings()
    {
        $map = Setting::all_map();
        $shop = request()->user()?->shop;
        if ($shop) {
            $map['slug'] = $shop->slug;
            $map['shop_name'] = $map['shop_name'] ?? $shop->name;
        }

        return response()->json($map);
    }

    public function saveSettings(Request $request)
    {
        // نقبل القيم الرقمية (مثل هامش الربح) ونخزّنها جميعًا كنصوص
        $coerced = [];
        foreach (self::SETTING_KEYS as $k) {
            if ($request->has($k) && is_scalar($request->input($k))) {
                $coerced[$k] = (string) $request->input($k);
            }
        }
        $request->merge($coerced);

        $rules = [];
        foreach (self::SETTING_KEYS as $k) $rules[$k] = 'nullable|string|max:1000';
        $data = $request->validate($rules);

        foreach ($data as $k => $v) {
            if ($request->has($k)) Setting::put($k, $v);
        }

        return response()->json(['message' => 'تم حفظ الإعدادات']);
    }

    // ================= helpers =================
    /** يربط التذكرة بملف عميل: المحدد، أو بحث بالهاتف، أو إنشاء ملف جديد */
    private function resolveCustomer(array $data, ?Ticket $ticket = null): ?int
    {
        if (!empty($data['customer_id']) && Customer::whereKey($data['customer_id'])->exists()) {
            return (int) $data['customer_id'];
        }
        $phone = trim($data['customer_phone'] ?? '');
        if ($phone === '') return $ticket?->customer_id;

        $existing = Customer::where('phone', $phone)->first();
        if ($existing) return $existing->id;

        return Customer::create(['name' => $data['customer_name'], 'phone' => $phone])->id;
    }

    private function ticketRow(Ticket $t): array
    {
        return [
            'id' => $t->id,
            'ticket_no' => $t->ticket_no,
            'customer_name' => $t->customer_name,
            'customer_phone' => $t->customer_phone,
            'device' => trim($t->make . ' ' . $t->model),
            'repair_type' => $t->repairType?->name,
            'price' => $t->price ? (float) $t->price : null,
            'status' => $t->status,
            'status_label' => Ticket::STATUS_LABELS[$t->status] ?? $t->status,
            'technician' => $t->technician?->name,
            'technician_id' => $t->technician_id,
            'customer_id' => $t->customer_id,
            'paid' => isset($t->paid_sum) ? (float) $t->paid_sum : $t->paid,
            'balance' => round((float) ($t->price ?? 0) - (isset($t->paid_sum) ? (float) $t->paid_sum : $t->paid), 2),
            'promised_at' => $t->promised_at?->toDateString(),
            'overdue' => $t->promised_at && in_array($t->status, Ticket::OPEN_STATUSES) && $t->promised_at->isPast(),
            'warranty_until' => $t->warranty_until,
            'created_at' => $t->created_at->format('Y-m-d H:i'),
        ];
    }

    private function statusList(): array
    {
        return collect(Ticket::STATUSES)
            ->map(fn ($s) => ['key' => $s, 'label' => Ticket::STATUS_LABELS[$s]])
            ->all();
    }

    private function logEvent(Ticket $ticket, string $status, ?string $note, ?int $userId): void
    {
        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'status' => $status,
            'note' => $note,
            'user_id' => $userId,
        ]);
    }

    private function syncMedia(Ticket $ticket, array $items): void
    {
        $ticket->media()->delete();
        foreach (array_values($items) as $i => $m) {
            $path = is_array($m) ? ($m['path'] ?? null) : $m;
            if (!$path) continue;
            TicketMedia::create([
                'ticket_id' => $ticket->id,
                'file_path' => $path,
                'kind' => is_array($m) ? ($m['kind'] ?? 'before') : 'before',
                'sort_order' => $i,
            ]);
        }
    }

    private function validatedTicket(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => 'nullable|integer',
            'customer_name' => 'required|string|max:120',
            'customer_phone' => 'required|string|max:30',
            'device_type' => 'nullable|in:phone,tablet,laptop,watch,other',
            'make' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:80',
            'imei' => 'nullable|string|max:40',
            'serial' => 'nullable|string|max:60',
            'repair_type_id' => 'nullable|exists:repair_types,id',
            'repair_type_ids' => 'nullable|array',
            'repair_type_ids.*' => 'integer|exists:repair_types,id',
            'description' => 'nullable|string|max:2000',
            'condition' => 'nullable|array',
            'accessories' => 'nullable|array',
            'accessories.*' => 'string|max:60',
            'device_password' => 'nullable|string|max:120',
            'estimated_cost' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'promised_at' => 'nullable|date',
            'warranty_months' => 'nullable|integer|min:0|max:36',
            'status' => 'required|in:' . implode(',', Ticket::STATUSES),
            'technician_id' => 'nullable|exists:technicians,id',
        ]);

        // توحيد الأعطال: المصفوفة هي المصدر، والعطل الرئيسي = أولها (للتوافق وحاسبة السعر)
        if (array_key_exists('repair_type_ids', $data)) {
            $ids = array_values(array_unique(array_map('intval', $data['repair_type_ids'] ?? [])));
            $data['repair_type_ids'] = $ids;
            $data['repair_type_id'] = $ids[0] ?? null;
        } elseif (! empty($data['repair_type_id'])) {
            $data['repair_type_ids'] = [(int) $data['repair_type_id']];
        }

        return $data;
    }

    private function validatedItem(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'sku' => 'nullable|string|max:60',
            'compatible' => 'nullable|string|max:120',
            'qty' => 'required|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:120',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'low_threshold' => 'nullable|integer|min:0',
        ]);
    }

    private function validatedTech(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:160',
            'specialty' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'active' => 'boolean',
        ]);
    }

    private function validatedRule(Request $request): array
    {
        return $request->validate([
            'make_id' => 'required|exists:makes,id',
            'car_model_id' => 'nullable|exists:car_models,id',
            'repair_type_id' => 'required|exists:repair_types,id',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:5|max:600',
            'warranty_months' => 'nullable|integer|min:0|max:36',
        ]);
    }
}
