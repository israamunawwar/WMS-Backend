<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndAdminSeeder::class);

        $this->seedCatalog();

        // هذه تعتمد على وجود المستخدمين والمواد أعلاه
        $this->call([
            SystemSeeder::class,
            OrderSeeder::class,
        ]);
    }

    private function seedCatalog(): void
    {
        $catalog = [
            'أجهزة حاسوب' => [
                ['Dell Monitor', 'شاشة ديل', 'IT-001', 20, 5],
                ['Keyboard', 'لوحة مفاتيح', 'IT-002', 40, 32],
                ['Mouse', 'ماوس', 'IT-003', 40, 3],
            ],
            'معدات شبكات' => [
                ['Cisco Router', 'راوتر سيسكو', 'NET-001', 8, 8],
                ['RJ45 Connector', 'رؤوس RJ45', 'NET-002', 500, 120],
                ['Cat6 Cable (m)', 'كابل Cat6 (متر)', 'NET-003', 500, 310],
            ],
            'كابلات ووصلات' => [
                ['HDMI Cable', 'كابل HDMI', 'CAB-001', 30, 2],
                ['VGA Cable', 'كابل VGA', 'CAB-002', 15, 0],
            ],
        ];

        foreach ($catalog as $categoryName => $items) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($items as [$nameEn, $nameAr, $barcode, $initial, $current]) {
                Item::firstOrCreate(['barcode' => $barcode], [
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                    'category_id' => $category->id,
                    'initial_balance' => $initial,
                    'current_stock' => $current,
                ]);
            }
        }
    }
}
