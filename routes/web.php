<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// كل المستخدمين المسجلين
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/export/excel', [ItemController::class, 'exportExcel'])->name('items.export.excel');
    Route::get('/items/export/pdf', [ItemController::class, 'exportPdf'])->name('items.export.pdf');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// رئيس القسم وأمين المستودع
Route::middleware(['auth', 'verified', 'role:super_admin|admin'])->group(function () {
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/resolve', [InventoryController::class, 'resolve'])->name('inventory.resolve');
    Route::post('/inventory/close', [InventoryController::class, 'close'])->name('inventory.close');

    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
});

// إدارة المستخدمين: رئيس القسم فقط
Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
    Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    Route::patch('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
});

require __DIR__.'/auth.php';
