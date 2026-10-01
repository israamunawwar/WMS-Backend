<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['site_name', 'نظام إدارة المستودعات', 'string'],
            ['low_stock_threshold', '5', 'integer'],
            ['default_borrow_days', '14', 'integer'],
        ] as [$key, $value, $type]) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'type' => $type]);
        }

        $head = User::where('email', 'head@it.edu')->first();
        $monitor = Item::where('barcode', 'IT-001')->first();
        $router = Item::where('barcode', 'NET-001')->first();

        if ($monitor && Maintenance::count() === 0) {
            Maintenance::create([
                'item_id' => $monitor->id,
                'reported_by' => $head?->id,
                'description' => 'تلف في شاشة العرض',
                'status' => Maintenance::PENDING,
            ]);
        }

        if ($router && Maintenance::where('item_id', $router->id)->doesntExist()) {
            Maintenance::create([
                'item_id' => $router->id,
                'reported_by' => $head?->id,
                'description' => 'عطل في اللوحة الأم',
                'notes' => 'شركة الصيانة المعتمدة',
                'status' => Maintenance::REPAIRING,
                'cost' => 0,
            ]);
        }

        if ($head && ActivityLog::count() === 0) {
            ActivityLog::create([
                'user_id' => $head->id,
                'action' => 'setting_update',
                'description' => 'تهيئة النظام وإضافة البيانات التجريبية',
            ]);
        }
    }
}
