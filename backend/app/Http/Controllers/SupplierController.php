<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** الموردون — لوحة التحكم */
class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = Supplier::withCount('items')->orderBy('name');
        if ($s = $request->input('search')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")
                ->orWhere('contact_name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
                ->orWhere('categories', 'like', "%$s%"));
        }

        return response()->json($q->get());
    }

    public function show(Supplier $supplier)
    {
        $items = $supplier->items()->orderBy('name')->get()->map(fn ($i) => $i->append('is_low'));

        // آخر التوريدات من هذا المورد (حركات شراء على قطعه)
        $purchases = StockMovement::with(['item:id,name', 'user:id,name'])
            ->whereIn('inventory_item_id', $items->pluck('id'))
            ->where('type', 'purchase')->latest()->limit(50)->get()
            ->map(fn ($m) => [
                'id' => $m->id, 'item' => $m->item?->name, 'qty' => $m->qty,
                'unit_cost' => $m->unit_cost, 'total' => round($m->qty * (float) ($m->unit_cost ?? 0), 2),
                'note' => $m->note, 'user' => $m->user?->name, 'created_at' => $m->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'supplier' => $supplier,
            'items' => $items,
            'purchases' => $purchases,
            'totals' => [
                'items' => $items->count(),
                'stock_value' => (float) $items->sum(fn ($i) => $i->qty * (float) ($i->cost ?? 0)),
                'purchased' => (float) $purchases->sum('total'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        return response()->json(Supplier::create($this->validated($request)), 201);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));

        return response()->json(['message' => 'تم التحديث', 'supplier' => $supplier]);
    }

    public function destroy(Supplier $supplier)
    {
        // القطع تبقى ويُفكّ ربطها فقط
        $supplier->delete();

        return response()->json(['message' => 'تم حذف المورد']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'contact_name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'whatsapp' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:160',
            'address' => 'nullable|string|max:200',
            'city' => 'nullable|string|max:80',
            'country' => 'nullable|string|max:80',
            'tax_number' => 'nullable|string|max:30',
            'payment_terms' => 'nullable|string|max:120',
            'bank_details' => 'nullable|string|max:300',
            'categories' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:2000',
            'active' => 'nullable|boolean',
        ]);
    }
}
