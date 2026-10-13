<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Make;
use App\Models\PriceRule;
use App\Models\RepairType;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Http\Request;

/** الموقع العام — بلا تسجيل دخول */
class SiteController extends Controller
{
    public function settings()
    {
        $map = Setting::all_map();
        $shop = app()->bound('currentShop') ? app('currentShop') : null;
        if ($shop) {
            $map['shop_name'] = $shop->name;
            $map['slug'] = $shop->slug;
            $map['logo'] = $shop->logo_path;
            $map['features'] = $shop->features ?? [];
        }

        return response()->json($map);
    }

    /** الماركات + الموديلات + أنواع الأعطال — تغذّي حاسبة السعر والحجز */
    public function catalog()
    {
        return response()->json([
            'makes' => Make::with(['models:id,make_id,name'])->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'models' => $m->models->map(fn ($x) => ['id' => $x->id, 'name' => $x->name]),
                ]),
            'repair_types' => RepairType::where('active', true)->orderBy('sort_order')
                ->get(['id', 'name', 'icon']),
        ]);
    }

    /** خدماتنا (أنواع الأعطال) لعرضها بالموقع */
    public function services()
    {
        return response()->json(
            RepairType::where('active', true)->orderBy('sort_order')->get(['id', 'name', 'icon'])
        );
    }

    /** حاسبة السعر الفوري — تُرجع السعر والمدة والضمان */
    public function quote(Request $request)
    {
        $data = $request->validate([
            'make_id' => 'required|exists:makes,id',
            'car_model_id' => 'nullable|exists:car_models,id',
            'repair_type_id' => 'required|exists:repair_types,id',
        ]);

        // ابحث عن سعر للموديل المحدد، وإلا سعر عام للماركة
        $rule = PriceRule::where('make_id', $data['make_id'])
            ->where('repair_type_id', $data['repair_type_id'])
            ->where(function ($q) use ($data) {
                $q->where('car_model_id', $data['car_model_id'] ?? null);
                if (!empty($data['car_model_id'])) {
                    $q->orWhereNull('car_model_id');
                }
            })
            ->orderByRaw('car_model_id IS NULL') // فضّل المطابقة الدقيقة
            ->first();

        if (!$rule) {
            return response()->json([
                'found' => false,
                'message' => 'السعر غير متوفر — تواصل معنا لتقدير دقيق',
            ]);
        }

        return response()->json([
            'found' => true,
            'price' => (float) $rule->price,
            'duration_minutes' => $rule->duration_minutes,
            'warranty_months' => $rule->warranty_months,
        ]);
    }

    /** حجز/طلب تسعير من الموقع */
    public function booking(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'make' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:80',
            'repair_type' => 'nullable|string|max:80',
            'service_type' => 'nullable|in:visit,pickup',
            'preferred_time' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:1000',
        ]);

        $booking = Booking::create($data + ['status' => 'new']);

        return response()->json([
            'message' => 'تم استلام حجزك — سنتواصل معك على واتساب قريباً',
            'booking_id' => $booking->id,
        ], 201);
    }

    /** تتبّع حالة التصليح برقم التذكرة أو رقم الجوال */
    public function track(Request $request)
    {
        $data = $request->validate([
            'q' => 'required|string|max:40',
        ]);

        $ticket = Ticket::with(['repairType:id,name', 'technician:id,name', 'events'])
            ->where(function ($q) use ($data) {
                $q->where('ticket_no', $data['q'])
                    ->orWhere('customer_phone', $data['q']);
            })
            ->latest()
            ->first();

        if (!$ticket) {
            return response()->json(['found' => false, 'message' => 'لم نجد تذكرة بهذا الرقم'], 404);
        }

        return response()->json([
            'found' => true,
            'ticket' => [
                'ticket_no' => $ticket->ticket_no,
                'customer_name' => $ticket->customer_name,
                'device' => trim($ticket->make . ' ' . $ticket->model),
                'repair_type' => $ticket->repairType?->name,
                'technician' => $ticket->technician?->name,
                'status' => $ticket->status,
                'status_label' => Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status,
                'warranty_months' => $ticket->warranty_months,
                'events' => $ticket->events->map(fn ($e) => [
                    'status' => $e->status,
                    'status_label' => Ticket::STATUS_LABELS[$e->status] ?? $e->status,
                    'note' => $e->note,
                    'at' => $e->created_at->format('Y-m-d H:i'),
                ]),
            ],
            'statuses' => collect(Ticket::STATUSES)
                ->reject(fn ($s) => $s === 'cancelled')
                ->map(fn ($s) => ['key' => $s, 'label' => Ticket::STATUS_LABELS[$s]])
                ->values(),
        ]);
    }
}
