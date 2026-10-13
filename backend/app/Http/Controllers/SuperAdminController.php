<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ShopProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** لوحة الإدارة العليا للمنصّة — إدارة كل المحلات والباقات. */
class SuperAdminController extends Controller
{
    /** إحصائيات عامة للمنصّة. */
    public function stats()
    {
        $byStatus = Shop::select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status');

        return response()->json([
            'shops_total' => Shop::count(),
            'shops_active' => (int) ($byStatus['active'] ?? 0),
            'shops_pending' => (int) ($byStatus['pending'] ?? 0),
            'shops_suspended' => (int) ($byStatus['suspended'] ?? 0),
            'shops_expired' => Shop::where('status', 'active')
                ->whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '<', now())->count(),
            'users_total' => User::where('role', '!=', 'super_admin')->count(),
            'tickets_total' => Ticket::count(),       // super-admin => بدون نطاق محل => الكل
            'plans_total' => Plan::count(),
        ]);
    }

    /** قائمة كل المحلات. */
    public function shops(Request $request)
    {
        $q = Shop::with('plan')->withCount(['users', 'tickets'])->latest();

        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        if ($term = $request->query('q')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$term%")
                ->orWhere('phone', 'like', "%$term%")
                ->orWhere('email', 'like', "%$term%"));
        }

        $shops = $q->get()->map(fn ($shop) => $this->present($shop));

        return response()->json($shops);
    }

    public function showShop(int $id)
    {
        $shop = Shop::with('plan')->withCount(['users', 'tickets'])->findOrFail($id);
        $owner = User::where('shop_id', $id)->where('role', 'admin')->first();

        return response()->json([
            'shop' => $this->present($shop),
            'owner' => $owner ? $owner->only(['id', 'name', 'email', 'phone']) : null,
        ]);
    }

    /** اعتماد محل بانتظار الموافقة → تفعيله. */
    public function approveShop(int $id)
    {
        $shop = Shop::findOrFail($id);
        $shop->status = 'active';
        $this->applyPlanPeriod($shop, $shop->plan);
        $shop->save();

        return response()->json(['message' => 'تم تفعيل المحل', 'shop' => $this->present($shop->fresh('plan'))]);
    }

    /** رفض طلب تسجيل محل (قيد الانتظار فقط) — يحذف المحل وصاحبه. */
    public function rejectShop(int $id)
    {
        $shop = Shop::findOrFail($id);
        if ($shop->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن رفض محل مُفعَّل — استخدم الإيقاف أو الحذف'], 422);
        }

        User::where('shop_id', $shop->id)->delete();
        $shop->delete();

        return response()->json(['message' => 'تم رفض الطلب']);
    }

    /** تعديل المحل: الحالة/الباقة/تاريخ الانتهاء/البيانات. */
    public function updateShop(Request $request, int $id)
    {
        $shop = Shop::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'phone' => 'sometimes|nullable|string|max:30',
            'email' => 'sometimes|nullable|email|max:160',
            'address' => 'sometimes|nullable|string|max:200',
            'status' => 'sometimes|in:pending,active,suspended',
            'plan_id' => 'sometimes|nullable|exists:plans,id',
            'subscription_ends_at' => 'sometimes|nullable|date',
            'features' => 'sometimes|array',
            'features.*' => 'string|in:' . implode(',', array_keys(Shop::AVAILABLE_FEATURES)),
        ]);

        $shop->fill($data);
        $shop->save();

        return response()->json(['message' => 'تم الحفظ', 'shop' => $this->present($shop->fresh('plan'))]);
    }

    /** تجديد الاشتراك: يمدّد حسب مدة الباقة من تاريخ الانتهاء الحالي (أو الآن). */
    public function renewShop(Request $request, int $id)
    {
        $shop = Shop::with('plan')->findOrFail($id);

        if ($planId = $request->input('plan_id')) {
            $shop->plan_id = $planId;
            $shop->setRelation('plan', Plan::find($planId));
        }

        $days = $shop->plan?->duration_days ?? 30;
        $from = ($shop->subscription_ends_at && $shop->subscription_ends_at->isFuture())
            ? $shop->subscription_ends_at
            : now();
        $shop->subscription_ends_at = $from->copy()->addDays($days);
        $shop->status = 'active';
        $shop->save();

        return response()->json(['message' => 'تم تجديد الاشتراك', 'shop' => $this->present($shop->fresh('plan'))]);
    }

    /** إنشاء محل مباشرة من الإدارة (فعّال فورًا). */
    public function createShop(Request $request)
    {
        $data = $request->validate([
            'shop_name' => 'required|string|max:120',
            'owner_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:160|unique:users,email',
            'password' => 'required|string|min:6',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $shop = Shop::create([
            'name' => $data['shop_name'],
            'slug' => Shop::makeSlug($data['shop_name']),
            'owner_name' => $data['owner_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'status' => 'active',
            'plan_id' => $data['plan_id'] ?? Plan::value('id'),
        ]);
        $this->applyPlanPeriod($shop, $shop->plan);
        $shop->save();

        User::create([
            'shop_id' => $shop->id,
            'name' => $data['owner_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin',
            'active' => true,
        ]);

        ShopProvisioner::seedDefaults($shop);

        return response()->json(['message' => 'تم إنشاء المحل وتفعيله', 'shop' => $this->present($shop->fresh('plan'))], 201);
    }

    public function destroyShop(int $id)
    {
        $shop = Shop::findOrFail($id);
        User::where('shop_id', $shop->id)->delete();
        $shop->delete();

        return response()->json(['message' => 'تم حذف المحل']);
    }

    // ---- الباقات ----

    public function plans()
    {
        return response()->json(Plan::withCount('shops')->orderBy('sort_order')->get());
    }

    public function storePlan(Request $request)
    {
        $data = $this->validatePlan($request);
        $data['slug'] = Plan::makeSlug($data['name']);
        $plan = Plan::create($data);

        return response()->json(['message' => 'تمت إضافة الباقة', 'plan' => $plan], 201);
    }

    public function updatePlan(Request $request, int $id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update($this->validatePlan($request));

        return response()->json(['message' => 'تم الحفظ', 'plan' => $plan]);
    }

    public function destroyPlan(int $id)
    {
        $plan = Plan::findOrFail($id);
        if ($plan->shops()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف باقة مرتبطة بمحلات'], 422);
        }
        $plan->delete();

        return response()->json(['message' => 'تم حذف الباقة']);
    }

    // ---- أدوات داخلية ----

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:80',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'max_technicians' => 'nullable|integer|min:1',
            'max_tickets_per_month' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    /** قائمة المميزات المتاحة على المنصّة (key => اسم معروض). */
    public function featureCatalog()
    {
        return response()->json(Shop::AVAILABLE_FEATURES);
    }

    private function applyPlanPeriod(Shop $shop, ?Plan $plan): void
    {
        if ($plan && $plan->duration_days) {
            $shop->subscription_ends_at = now()->addDays($plan->duration_days);
        }
    }

    private function present(Shop $shop): array
    {
        return array_merge($shop->toArray(), [
            'access_state' => $shop->accessState(),
            'public_url' => '/s/' . $shop->slug,
        ]);
    }
}
