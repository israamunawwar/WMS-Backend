<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Order;
use App\Models\Setting;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. إحصائيات المخزون (نفس تعريفات فلاتر صفحة المواد)
        $totalItems = Item::count();
        $totalCategories = Category::count();
        $lowStockItems = Item::where('current_stock', '>', 0)->where('current_stock', '<=', Setting::lowStockThreshold())->count();
        $missingItems = Item::where('current_stock', 0)->count();
        $damagedItems = Maintenance::where('status', '!=', 'fixed')->distinct()->count('item_id');

        $mostUsedItem = Item::whereColumn('initial_balance', '>', 'current_stock')
            ->orderByRaw('(initial_balance - current_stock) DESC')
            ->first();
        $mostUsedItemName = $mostUsedItem ? ($mostUsedItem->name_ar ?? $mostUsedItem->name_en) : '—';

        // 2. إحصائيات الطلبات
        $pendingOrders = Order::awaitingDecision()->count();
        $todayOrders = Order::whereDate('created_at', today())->count();
        $monthOrders = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $rejectedOrders = Order::where('status', Order::REJECTED)->count();

        // 3. المدربون والمخابر
        $topLab = Order::whereNotNull('destination')
            ->selectRaw('destination, COUNT(*) as total')
            ->groupBy('destination')
            ->orderByDesc('total')
            ->value('destination') ?? '—';

        $topTrainer = Order::with('user')
            ->selectRaw('user_id, COUNT(*) as total')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->first()?->user?->name ?? '—';

        return view('dashboard', compact(
            'totalItems', 'totalCategories', 'lowStockItems',
            'damagedItems', 'missingItems', 'mostUsedItemName',
            'todayOrders', 'monthOrders', 'rejectedOrders', 'pendingOrders',
            'topLab', 'topTrainer'
        ));
    }
}
