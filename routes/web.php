<?php

use App\Http\Controllers\Admin\ConsignmentController;
use App\Http\Controllers\Admin\OnlineOrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\WalkInOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Staff\ProductionController;
use App\Http\Controllers\Staff\RawMaterialController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

// ─── Public welcome → redirect to login ──────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));

// ─── Authenticated shared routes ──────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

// ─── Super Admin routes ───────────────────────────────────────────────────────
Route::middleware(['auth', 'role:super_admin'])
    ->prefix('super')
    ->name('super.')
    ->group(function () {
        // User management
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');

        // Subscriptions / T&C
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::put('subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
    });

// ─── Admin (Owner) routes ─────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Walk-in orders (POS)
        Route::get('walk-in', [WalkInOrderController::class, 'index'])->name('walk-in.index');
        Route::get('walk-in/create', [WalkInOrderController::class, 'create'])->name('walk-in.create');
        Route::post('walk-in', [WalkInOrderController::class, 'store'])->name('walk-in.store');
        Route::get('walk-in/search', [WalkInOrderController::class, 'search'])->name('walk-in.search');

        // Online orders
        Route::get('online', [OnlineOrderController::class, 'index'])->name('online.index');
        Route::get('online/create', [OnlineOrderController::class, 'create'])->name('online.create');
        Route::post('online', [OnlineOrderController::class, 'store'])->name('online.store');
        Route::patch('online/{transaction}/shipment', [OnlineOrderController::class, 'updateShipment'])->name('online.shipment');
        Route::get('online/{transaction}', [OnlineOrderController::class, 'show'])->name('online.show');

        // Payments (standalone confirm for online orders)
        Route::post('payments/{transaction}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');

        // Receipts
        Route::get('receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
        Route::post('receipts/{receipt}/reprint', [ReceiptController::class, 'reprint'])->name('receipts.reprint');
        Route::post('receipts/issue/{transaction}', [ReceiptController::class, 'issue'])->name('receipts.issue');

        // Returns / Replacements
        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::get('returns/create/{salesItem}', [ReturnController::class, 'create'])->name('returns.create');
        Route::post('returns', [ReturnController::class, 'store'])->name('returns.store');

        // Products
        Route::resource('products', ProductController::class)->except(['show']);

        // Suppliers
        Route::resource('suppliers', SupplierController::class)->except(['show']);

        // Stock management
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/adjust/{variant}', [StockController::class, 'adjustForm'])->name('stock.adjust-form');
        Route::post('stock/adjust/{variant}', [StockController::class, 'adjust'])->name('stock.adjust');
        Route::get('stock/import', [StockController::class, 'importForm'])->name('stock.import-form');
        Route::post('stock/import', [StockController::class, 'import'])->name('stock.import');

        // Consignment
        Route::get('consignment', [ConsignmentController::class, 'index'])->name('consignment.index');
        Route::get('consignment/partners/create', [ConsignmentController::class, 'createPartner'])->name('consignment.partners.create');
        Route::post('consignment/partners', [ConsignmentController::class, 'storePartner'])->name('consignment.partners.store');
        Route::get('consignment/partners/{partner}/edit', [ConsignmentController::class, 'editPartner'])->name('consignment.partners.edit');
        Route::put('consignment/partners/{partner}', [ConsignmentController::class, 'updatePartner'])->name('consignment.partners.update');
        Route::get('consignment/items/create', [ConsignmentController::class, 'createItem'])->name('consignment.items.create');
        Route::post('consignment/items', [ConsignmentController::class, 'storeItem'])->name('consignment.items.store');
        Route::get('consignment/payouts', [ConsignmentController::class, 'payouts'])->name('consignment.payouts');
        Route::post('consignment/payouts', [ConsignmentController::class, 'generatePayout'])->name('consignment.payouts.generate');
        Route::patch('consignment/payouts/{payment}/mark-paid', [ConsignmentController::class, 'markPaid'])->name('consignment.payouts.mark-paid');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/stock-movement', [ReportController::class, 'stockMovement'])->name('reports.stock-movement');
        Route::get('reports/best-sellers', [ReportController::class, 'bestSellers'])->name('reports.best-sellers');
        Route::get('reports/consignment', [ReportController::class, 'consignment'])->name('reports.consignment');
    });

// ─── Staff routes ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        // Production log
        Route::get('production', [ProductionController::class, 'index'])->name('production.index');
        Route::get('production/create', [ProductionController::class, 'create'])->name('production.create');
        Route::post('production', [ProductionController::class, 'store'])->name('production.store');
        Route::get('production/{log}', [ProductionController::class, 'show'])->name('production.show');

        // Raw materials
        Route::get('materials', [RawMaterialController::class, 'index'])->name('materials.index');
        Route::get('materials/create', [RawMaterialController::class, 'create'])->name('materials.create');
        Route::post('materials', [RawMaterialController::class, 'store'])->name('materials.store');
        Route::get('materials/{material}/edit', [RawMaterialController::class, 'edit'])->name('materials.edit');
        Route::put('materials/{material}', [RawMaterialController::class, 'update'])->name('materials.update');
        Route::post('materials/{material}/receive', [RawMaterialController::class, 'receive'])->name('materials.receive');
    });
