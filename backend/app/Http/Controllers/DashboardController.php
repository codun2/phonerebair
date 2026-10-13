<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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
        $q = Ticket::with(['repairType:id,name', 'technician:id,name'])->latest();
        if ($request->filled('status')) $q->where('status', $request->input('status'));
        if ($s = $request->input('search')) {
            $q->where(fn ($w) => $w->where('ticket_no', 'like', "%$s%")
                ->orWhere('customer_name', 'like', "%$s%")
                ->orWhere('customer_phone', 'like', "%$s%")
                ->orWhere('imei', 'like', "%$s%"));
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
        $ticket->load(['repairType', 'technician', 'media', 'events.user:id,name']);

        return response()->json([
            'ticket' => $ticket,
            'status_label' => Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status,
            'statuses' => $this->statusList(),
        ]);
    }

    public function storeTicket(Request $request)
    {
        $data = $this->validatedTicket($request);
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
        return response()->json(InventoryItem::orderBy('name')->get()
            ->map(fn ($i) => $i->append('is_low')));
    }

    public function storeItem(Request $request)
    {
        return response()->json(InventoryItem::create($this->validatedItem($request)), 201);
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        $item->update($this->validatedItem($request));

        return response()->json(['message' => 'تم التحديث', 'item' => $item]);
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
        $rules = [];
        foreach (self::SETTING_KEYS as $k) $rules[$k] = 'nullable|string|max:1000';
        $data = $request->validate($rules);

        foreach ($data as $k => $v) {
            if ($request->has($k)) Setting::put($k, $v);
        }

        return response()->json(['message' => 'تم حفظ الإعدادات']);
    }

    // ================= helpers =================
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
        return $request->validate([
            'customer_name' => 'required|string|max:120',
            'customer_phone' => 'required|string|max:30',
            'make' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:80',
            'imei' => 'nullable|string|max:40',
            'repair_type_id' => 'nullable|exists:repair_types,id',
            'description' => 'nullable|string|max:2000',
            'price' => 'nullable|numeric|min:0',
            'warranty_months' => 'nullable|integer|min:0|max:36',
            'status' => 'required|in:' . implode(',', Ticket::STATUSES),
            'technician_id' => 'nullable|exists:technicians,id',
        ]);
    }

    private function validatedItem(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'sku' => 'nullable|string|max:60',
            'compatible' => 'nullable|string|max:120',
            'qty' => 'required|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'low_threshold' => 'nullable|integer|min:0',
        ]);
    }

    private function validatedTech(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'specialty' => 'nullable|string|max:100',
            'active' => 'boolean',
        ]);
    }

    private function validatedRule(Request $request): array
    {
        return $request->validate([
            'make_id' => 'required|exists:makes,id',
            'car_model_id' => 'nullable|exists:car_models,id',
            'repair_type_id' => 'required|exists:repair_types,id',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:5|max:600',
            'warranty_months' => 'nullable|integer|min:0|max:36',
        ]);
    }
}
