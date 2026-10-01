<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $trainer = User::where('email', 'trainer@it.edu')->first();
        $head = User::where('email', 'head@it.edu')->first();

        if (! $trainer || ! $head || Order::exists()) {
            return;
        }

        $items = Item::pluck('id', 'barcode');

        // [المستخدم، الوجهة، الأولوية، الحالة، ملاحظات القرار، منذ كم يوم، المواد [باركود => كمية]]
        $orders = [
            [$trainer, 'مخبر الشبكات (Lab 3)', 'حساس', Order::PENDING, null, 0, ['NET-002' => 20, 'NET-003' => 30]],
            [$trainer, 'مخبر البرمجيات (Lab 1)', 'عادي', Order::APPROVED, 'تم الاعتماد', 0, ['IT-002' => 2]],
            [$head, 'قسم الإدارة', 'عادي', Order::REJECTED, 'الكمية غير مبررة', 3, ['IT-003' => 1]],
            [$trainer, 'مخبر الصيانة', 'عادي', Order::NEW, null, 0, ['CAB-001' => 1]],
            [$trainer, 'مخبر الشبكات (Lab 2)', 'حساس', Order::PENDING, null, 31, ['NET-001' => 1]],
        ];

        foreach ($orders as [$user, $destination, $priority, $status, $notes, $daysAgo, $lines]) {
            $order = Order::create([
                'user_id' => $user->id,
                'destination' => $destination,
                'priority' => $priority,
                'status' => $status,
                'notes' => $notes,
            ]);

            $order->forceFill(['created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays($daysAgo)])->save();

            foreach ($lines as $barcode => $quantity) {
                $order->items()->create(['item_id' => $items[$barcode], 'quantity' => $quantity]);
            }
        }
    }
}
