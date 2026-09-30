<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicLinkController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::put('/dashboard/page', [DashboardController::class, 'updatePage'])->middleware('throttle:editor')->name('dashboard.page.update');
    Route::post('/dashboard/links', [DashboardController::class, 'storeLink'])->middleware('throttle:editor')->name('dashboard.links.store');
    Route::put('/dashboard/links/reorder', [DashboardController::class, 'reorderLinks'])->middleware('throttle:editor')->name('dashboard.links.reorder');
    Route::put('/dashboard/links/{link}', [DashboardController::class, 'updateLink'])->middleware('throttle:editor')->name('dashboard.links.update');
    Route::delete('/dashboard/links/{link}', [DashboardController::class, 'destroyLink'])->middleware('throttle:editor')->name('dashboard.links.destroy');
    Route::post('/dashboard/avatar', [DashboardController::class, 'updateAvatar'])->middleware('throttle:editor')->name('dashboard.avatar.update');
    Route::post('/dashboard/publish', [DashboardController::class, 'publish'])->middleware('throttle:publish')->name('dashboard.publish');
    Route::get('/dashboard/credits', [CreditController::class, 'index'])->name('credits.index');
    Route::post('/dashboard/checkout/{creditPackage}', [CreditController::class, 'checkout'])->middleware('throttle:checkout')->name('credits.checkout');
    Route::get('/dashboard/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});

Route::prefix('admin')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::patch('/users/{user}/status', [AdminController::class, 'updateUserStatus'])->name('admin.users.status');
    Route::post('/users/{user}/credit-adjustments', [AdminController::class, 'adjustCredits'])->name('admin.users.credits');
    Route::patch('/pages/{page}/status', [AdminController::class, 'updatePageStatus'])->name('admin.pages.status');
    Route::post('/credit-packages', [AdminController::class, 'storePackage'])->name('admin.packages.store');
    Route::patch('/credit-packages/{creditPackage}', [AdminController::class, 'updatePackage'])->name('admin.packages.update');
});

Route::post('/webhooks/mercado-pago', [MercadoPagoWebhookController::class, 'store'])
    ->middleware('throttle:webhooks')
    ->name('webhooks.mercadopago');

Route::get('/health/ready', [HealthController::class, 'ready'])
    ->middleware('throttle:public')
    ->name('health.ready');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::post('/l/{token}/reservation', [PublicLinkController::class, 'reserve'])->middleware('throttle:public')->name('public.reservation');
Route::get('/l/{token}', [PublicLinkController::class, 'show'])->middleware('throttle:public')->name('public.link');

Route::get('/{username}', [PublicPageController::class, 'show'])
    ->middleware('throttle:public')
    ->where('username', '[a-z]+(?:-[a-z]+)*')
    ->name('public.page');
