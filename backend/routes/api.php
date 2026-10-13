<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        $db = 'connected';
    } catch (\Throwable $e) {
        $db = 'error';
    }

    return response()->json(['message' => 'MobileFix API up', 'db' => $db, 'host' => request()->getHost()]);
});

// ---- الباقات (للموقع التعريفي العام) ----
Route::get('/plans', fn () => \App\Models\Plan::where('is_active', true)
    ->orderBy('sort_order')->get(['id', 'name', 'slug', 'price', 'duration_days', 'description', 'max_technicians']));

// ---- تسجيل محل جديد (self-signup → بانتظار الموافقة) ----
Route::post('/register-shop', [App\Http\Controllers\ShopRegistrationController::class, 'register']);

// ---- الموقع العام لكل محل: /api/shops/{slug}/... ----
Route::prefix('shops/{shopSlug}')->middleware('shop.slug')->group(function () {
    Route::get('/settings', [SiteController::class, 'settings']);
    Route::get('/catalog', [SiteController::class, 'catalog']);       // ماركات/موديلات/أعطال
    Route::get('/services', [SiteController::class, 'services']);
    Route::post('/quote', [SiteController::class, 'quote']);          // حاسبة السعر
    Route::post('/booking', [SiteController::class, 'booking']);      // حجز
    Route::post('/track', [SiteController::class, 'track']);          // تتبّع حالة التصليح
});

// ---- دخول ----
Route::post('/login', [AuthController::class, 'login']);

// ---- لوحة الإدارة العليا للمنصّة (super-admin) ----
Route::prefix('super')->middleware(['auth:sanctum', 'super_admin'])->group(function () {
    Route::get('/stats', [App\Http\Controllers\SuperAdminController::class, 'stats']);
    Route::get('/feature-catalog', [App\Http\Controllers\SuperAdminController::class, 'featureCatalog']);

    Route::get('/shops', [App\Http\Controllers\SuperAdminController::class, 'shops']);
    Route::post('/shops', [App\Http\Controllers\SuperAdminController::class, 'createShop']);
    Route::get('/shops/{id}', [App\Http\Controllers\SuperAdminController::class, 'showShop']);
    Route::put('/shops/{id}', [App\Http\Controllers\SuperAdminController::class, 'updateShop']);
    Route::post('/shops/{id}/approve', [App\Http\Controllers\SuperAdminController::class, 'approveShop']);
    Route::post('/shops/{id}/reject', [App\Http\Controllers\SuperAdminController::class, 'rejectShop']);
    Route::post('/shops/{id}/renew', [App\Http\Controllers\SuperAdminController::class, 'renewShop']);
    Route::delete('/shops/{id}', [App\Http\Controllers\SuperAdminController::class, 'destroyShop']);

    Route::get('/plans', [App\Http\Controllers\SuperAdminController::class, 'plans']);
    Route::post('/plans', [App\Http\Controllers\SuperAdminController::class, 'storePlan']);
    Route::put('/plans/{id}', [App\Http\Controllers\SuperAdminController::class, 'updatePlan']);
    Route::delete('/plans/{id}', [App\Http\Controllers\SuperAdminController::class, 'destroyPlan']);
});

// ---- لوحة إدارة المحل ----
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/uploads', [UploadController::class, 'store']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // التذاكر
    Route::get('/dashboard/tickets', [DashboardController::class, 'tickets']);
    Route::get('/dashboard/tickets/{ticket}', [DashboardController::class, 'ticket']);
    Route::post('/dashboard/tickets', [DashboardController::class, 'storeTicket']);
    Route::put('/dashboard/tickets/{ticket}', [DashboardController::class, 'updateTicket']);
    Route::post('/dashboard/tickets/{ticket}/status', [DashboardController::class, 'ticketStatus']);
    Route::delete('/dashboard/tickets/{ticket}', [DashboardController::class, 'destroyTicket']);

    // المخزون
    Route::get('/dashboard/inventory', [DashboardController::class, 'inventory']);
    Route::post('/dashboard/inventory', [DashboardController::class, 'storeItem']);
    Route::put('/dashboard/inventory/{item}', [DashboardController::class, 'updateItem']);
    Route::delete('/dashboard/inventory/{item}', [DashboardController::class, 'destroyItem']);

    // الفنيون
    Route::get('/dashboard/technicians', [DashboardController::class, 'technicians']);
    Route::post('/dashboard/technicians', [DashboardController::class, 'storeTechnician']);
    Route::put('/dashboard/technicians/{technician}', [DashboardController::class, 'updateTechnician']);
    Route::delete('/dashboard/technicians/{technician}', [DashboardController::class, 'destroyTechnician']);

    // الحجوزات
    Route::get('/dashboard/bookings', [DashboardController::class, 'bookings']);
    Route::post('/dashboard/bookings/{booking}/status', [DashboardController::class, 'bookingStatus']);

    // أنواع الأعطال
    Route::get('/dashboard/repair-types', [DashboardController::class, 'repairTypes']);
    Route::post('/dashboard/repair-types', [DashboardController::class, 'storeRepairType']);
    Route::put('/dashboard/repair-types/{repairType}', [DashboardController::class, 'updateRepairType']);
    Route::delete('/dashboard/repair-types/{repairType}', [DashboardController::class, 'destroyRepairType']);

    // جدول الأسعار
    Route::get('/dashboard/price-rules', [DashboardController::class, 'priceRules']);
    Route::post('/dashboard/price-rules', [DashboardController::class, 'storePriceRule']);
    Route::put('/dashboard/price-rules/{priceRule}', [DashboardController::class, 'updatePriceRule']);
    Route::delete('/dashboard/price-rules/{priceRule}', [DashboardController::class, 'destroyPriceRule']);

    // الكتالوج (ماركات/موديلات)
    Route::get('/dashboard/catalog', [DashboardController::class, 'catalog']);
    Route::post('/dashboard/makes', [DashboardController::class, 'storeMake']);
    Route::put('/dashboard/makes/{make}', [DashboardController::class, 'updateMake']);
    Route::delete('/dashboard/makes/{make}', [DashboardController::class, 'deleteMake']);
    Route::post('/dashboard/makes/{make}/models', [DashboardController::class, 'storeModel']);
    Route::put('/dashboard/models/{carModel}', [DashboardController::class, 'updateModel']);
    Route::delete('/dashboard/models/{carModel}', [DashboardController::class, 'deleteModel']);

    // الإعدادات
    Route::get('/dashboard/settings', [DashboardController::class, 'getSettings']);
    Route::put('/dashboard/settings', [DashboardController::class, 'saveSettings']);
});
