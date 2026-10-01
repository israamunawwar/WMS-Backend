<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'low_stock_threshold' => Setting::lowStockThreshold(),
            'default_borrow_days' => (int) Setting::get('default_borrow_days'),
            'site_name' => Setting::get('site_name'),
        ];

        $categories = Category::withCount('items')->orderBy('name')->get();

        return view('settings.index', compact('settings', 'categories'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'low_stock_threshold' => 'required|integer|min:1|max:100000',
            'default_borrow_days' => 'required|integer|min:1|max:365',
            'site_name' => 'required|string|max:100',
        ]);

        Setting::set('low_stock_threshold', $data['low_stock_threshold'], 'integer');
        Setting::set('default_borrow_days', $data['default_borrow_days'], 'integer');
        Setting::set('site_name', $data['site_name']);

        ActivityLog::record('setting_update', 'حدّث إعدادات النظام');

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ]);

        Category::create($data);

        ActivityLog::record('category_create', "أضاف التصنيف \"{$data['name']}\"");

        return back()->with('success', 'تمت إضافة التصنيف بنجاح.');
    }

    public function destroyCategory(Category $category)
    {
        // حذف التصنيف يحذف موادها (cascade)، لذلك لا نسمح به إلا إذا كان فارغاً
        if ($category->items()->exists()) {
            return back()->with('error', 'لا يمكن حذف تصنيف يحتوي على مواد. انقلي المواد أولاً.');
        }

        $category->delete();

        ActivityLog::record('category_delete', "حذف التصنيف \"{$category->name}\"");

        return back()->with('success', 'تم حذف التصنيف.');
    }
}
