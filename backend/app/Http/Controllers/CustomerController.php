<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Http\Request;

/** ملفات العملاء وسجل أجهزتهم — لوحة التحكم */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = Customer::withCount('tickets')
            ->withSum('tickets as charged_sum', 'price')
            ->latest();

        if ($s = $request->input('search')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
                ->orWhere('alt_phone', 'like', "%$s%"));
        }

        $customers = $q->limit(300)->get();

        // المدفوع لكل عميل باستعلام واحد (عبر تذاكره)
        $paid = \App\Models\Payment::selectRaw('tickets.customer_id, SUM(payments.amount) as total')
            ->join('tickets', 'tickets.id', '=', 'payments.ticket_id')
            ->whereIn('tickets.customer_id', $customers->pluck('id'))
            ->groupBy('tickets.customer_id')->pluck('total', 'customer_id');

        return response()->json($customers->map(fn ($c) => [
            'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone,
            'alt_phone' => $c->alt_phone, 'email' => $c->email, 'address' => $c->address,
            'notes' => $c->notes,
            'tickets_count' => $c->tickets_count,
            'charged' => (float) ($c->charged_sum ?? 0),
            'paid' => (float) ($paid[$c->id] ?? 0),
            'balance' => round((float) ($c->charged_sum ?? 0) - (float) ($paid[$c->id] ?? 0), 2),
            'created_at' => $c->created_at->format('Y-m-d'),
        ]));
    }

    public function show(Customer $customer)
    {
        $tickets = $customer->tickets()->with(['repairType:id,name', 'technician:id,name'])
            ->withSum('payments as paid_sum', 'amount')->latest()->get();

        $charged = (float) $tickets->whereNotIn('status', ['cancelled', 'rejected', 'unrepairable'])->sum('price');
        $paid = (float) $tickets->sum('paid_sum');

        return response()->json([
            'customer' => $customer,
            'tickets' => $tickets->map(fn ($t) => [
                'id' => $t->id, 'ticket_no' => $t->ticket_no,
                'device' => trim($t->make . ' ' . $t->model),
                'device_type' => $t->device_type, 'imei' => $t->imei, 'serial' => $t->serial,
                'repair_type' => $t->repairType?->name,
                'price' => $t->price ? (float) $t->price : null,
                'paid' => (float) ($t->paid_sum ?? 0),
                'balance' => round((float) ($t->price ?? 0) - (float) ($t->paid_sum ?? 0), 2),
                'status' => $t->status,
                'status_label' => Ticket::STATUS_LABELS[$t->status] ?? $t->status,
                'warranty_until' => $t->warranty_until,
                'warranty_active' => $t->warranty_until && $t->warranty_until >= today()->toDateString(),
                'created_at' => $t->created_at->format('Y-m-d'),
            ]),
            'totals' => [
                'tickets' => $tickets->count(),
                'charged' => $charged,
                'paid' => $paid,
                'balance' => round($charged - $paid, 2),
                'active_warranties' => $tickets->filter(fn ($t) => $t->warranty_until && $t->warranty_until >= today()->toDateString())->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (Customer::where('phone', $data['phone'])->exists()) {
            return response()->json(['message' => 'يوجد عميل مسجّل بهذا الرقم'], 422);
        }

        return response()->json(Customer::create($data), 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validated($request);
        if (Customer::where('phone', $data['phone'])->where('id', '!=', $customer->id)->exists()) {
            return response()->json(['message' => 'يوجد عميل آخر بهذا الرقم'], 422);
        }
        $customer->update($data);
        // إبقاء بيانات التذاكر المعروضة متطابقة مع الملف
        Ticket::where('customer_id', $customer->id)
            ->update(['customer_name' => $customer->name, 'customer_phone' => $customer->phone]);

        return response()->json(['message' => 'تم التحديث', 'customer' => $customer]);
    }

    public function destroy(Customer $customer)
    {
        if ($customer->tickets()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف عميل له تذاكر — احذف تذاكره أولاً'], 422);
        }
        $customer->delete();

        return response()->json(['message' => 'تم حذف العميل']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'customer_type' => 'nullable|in:individual,business',
            'phone' => 'required|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|string|max:200',
            'city' => 'nullable|string|max:80',
            'company' => 'nullable|string|max:120',
            'tax_number' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:2000',
        ]);
    }
}
