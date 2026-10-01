<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $head;
    private User $keeper;
    private User $trainer;
    private Item $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'trainer'] as $role) {
            Role::create(['name' => $role]);
        }

        $this->head = tap(User::factory()->create(['name' => 'رئيس القسم']), fn ($u) => $u->assignRole('super_admin'));
        $this->keeper = tap(User::factory()->create(['name' => 'أمين المستودع']), fn ($u) => $u->assignRole('admin'));
        $this->trainer = tap(User::factory()->create(), fn ($u) => $u->assignRole('trainer'));

        $category = Category::create(['name' => 'أجهزة']);
        $this->monitor = Item::create(['name_en' => 'Monitor', 'name_ar' => 'شاشة', 'category_id' => $category->id, 'initial_balance' => 10, 'current_stock' => 10]);
    }

    // ---------- الصيانة ----------

    public function test_damage_can_be_reported_and_goes_to_pending(): void
    {
        $this->actingAs($this->keeper)->post('/maintenance', [
            'item_id' => $this->monitor->id, 'description' => 'شاشة مكسورة',
        ])->assertSessionHas('success');

        $m = Maintenance::firstOrFail();
        $this->assertSame(Maintenance::PENDING, $m->status);
        $this->assertSame($this->keeper->id, $m->reported_by);
        $this->assertDatabaseHas('activity_logs', ['action' => 'report_damage']);
    }

    public function test_maintenance_decisions_follow_the_workflow(): void
    {
        $m = Maintenance::create(['item_id' => $this->monitor->id, 'description' => 'x', 'status' => Maintenance::PENDING]);

        // لا يمكن "تم الإصلاح" قبل الإرسال للصيانة
        $this->actingAs($this->head)->patch("/maintenance/{$m->id}", ['decision' => 'fix'])->assertSessionHas('error');
        $this->assertSame(Maintenance::PENDING, $m->fresh()->status);

        // الإرسال للصيانة يتطلب ملاحظة (جهة الصيانة)
        $this->actingAs($this->head)->patch("/maintenance/{$m->id}", ['decision' => 'repair'])->assertSessionHasErrors('notes');

        $this->actingAs($this->head)->patch("/maintenance/{$m->id}", ['decision' => 'repair', 'notes' => 'شركة س'])->assertSessionHas('success');
        $this->assertSame(Maintenance::REPAIRING, $m->fresh()->status);

        $this->actingAs($this->head)->patch("/maintenance/{$m->id}", ['decision' => 'fix', 'cost' => 15000])->assertSessionHas('success');
        $m->refresh();
        $this->assertSame(Maintenance::FIXED, $m->status);
        $this->assertEquals(15000, $m->cost);

        // السجل المغلق لا يتغير
        $this->actingAs($this->head)->patch("/maintenance/{$m->id}", ['decision' => 'scrap', 'notes' => 'x'])->assertSessionHas('error');
        $this->assertSame(Maintenance::FIXED, $m->fresh()->status);
    }

    public function test_maintenance_page_renders_and_filters_pending(): void
    {
        Maintenance::create(['item_id' => $this->monitor->id, 'description' => 'عطل-قيد-القرار', 'status' => Maintenance::PENDING]);
        Maintenance::create(['item_id' => $this->monitor->id, 'description' => 'عطل-مغلق', 'status' => Maintenance::FIXED]);

        $this->actingAs($this->keeper)->get('/maintenance')->assertOk()->assertSee('عطل-قيد-القرار')->assertSee('عطل-مغلق');
        $this->actingAs($this->keeper)->get('/maintenance?filter=pending')->assertOk()->assertSee('عطل-قيد-القرار')->assertDontSee('عطل-مغلق');
    }

    public function test_trainer_cannot_use_maintenance(): void
    {
        $m = Maintenance::create(['item_id' => $this->monitor->id, 'description' => 'x', 'status' => Maintenance::PENDING]);

        $this->actingAs($this->trainer)->post('/maintenance', ['item_id' => $this->monitor->id, 'description' => 'x'])->assertForbidden();
        $this->actingAs($this->trainer)->patch("/maintenance/{$m->id}", ['decision' => 'scrap', 'notes' => 'x'])->assertForbidden();
    }

    // ---------- سجل العمليات ----------

    public function test_logs_page_shows_entries_and_filters_by_search_and_date(): void
    {
        $today = ActivityLog::create(['user_id' => $this->keeper->id, 'action' => 'create_order', 'description' => 'عملية-اليوم']);
        $old = ActivityLog::create(['user_id' => $this->head->id, 'action' => 'approve_order', 'description' => 'عملية-قديمة']);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->actingAs($this->head)->get('/logs')->assertOk()->assertSee('عملية-اليوم')->assertSee('عملية-قديمة');
        $this->actingAs($this->head)->get('/logs?search='.urlencode('رئيس القسم'))->assertOk()->assertSee('عملية-قديمة')->assertDontSee('عملية-اليوم');
        $this->actingAs($this->head)->get('/logs?date='.now()->format('Y-m-d'))->assertOk()->assertSee('عملية-اليوم')->assertDontSee('عملية-قديمة');
    }

    public function test_sensitive_actions_are_logged(): void
    {
        $this->actingAs($this->head)->post('/users', [
            'name' => 'مدرب جديد', 'email' => 'n@it.edu', 'password' => 'password123', 'role' => 'trainer',
        ]);
        $this->actingAs($this->head)->post('/inventory', ['name' => 'جرد']);

        $this->assertDatabaseHas('activity_logs', ['user_id' => $this->head->id, 'action' => 'user_create']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $this->head->id, 'action' => 'inventory_start']);
    }

    // ---------- الإعدادات والأصناف ----------

    public function test_settings_are_saved_and_drive_low_stock_threshold(): void
    {
        Item::create(['name_en' => 'Cheap', 'category_id' => $this->monitor->category_id, 'current_stock' => 8]);

        $this->assertSame(5, Setting::lowStockThreshold());
        $this->actingAs($this->head)->get('/items?type=low_stock')->assertDontSee('Cheap');

        $this->actingAs($this->head)->put('/settings', [
            'low_stock_threshold' => 10, 'default_borrow_days' => 30, 'site_name' => 'مستودع المعهد',
        ])->assertSessionHas('success');

        $this->assertSame(10, Setting::lowStockThreshold());
        $this->assertSame('مستودع المعهد', Setting::get('site_name'));
        $this->actingAs($this->head)->get('/items?type=low_stock')->assertSee('Cheap');
        // الشاشة (10) والمادة الرخيصة (8) كلتاهما ضمن الحد الجديد
        $this->assertSame(2, $this->actingAs($this->head)->get('/dashboard')->viewData('lowStockItems'));
    }

    public function test_settings_validation(): void
    {
        $this->actingAs($this->head)->put('/settings', [
            'low_stock_threshold' => 0, 'default_borrow_days' => 'abc', 'site_name' => '',
        ])->assertSessionHasErrors(['low_stock_threshold', 'default_borrow_days', 'site_name']);
    }

    public function test_categories_can_be_added_but_non_empty_ones_cannot_be_deleted(): void
    {
        $this->actingAs($this->head)->post('/settings/categories', ['name' => 'كابلات'])->assertSessionHas('success');
        $this->assertDatabaseHas('categories', ['name' => 'كابلات']);

        // اسم مكرر
        $this->actingAs($this->head)->post('/settings/categories', ['name' => 'كابلات'])->assertSessionHasErrors('name');

        // الحذف يُمنع إذا فيه مواد (لأن الحذف يتتالى على المواد)
        $this->actingAs($this->head)->delete('/settings/categories/'.$this->monitor->category_id)->assertSessionHas('error');
        $this->assertDatabaseHas('items', ['id' => $this->monitor->id]);

        $empty = Category::where('name', 'كابلات')->first();
        $this->actingAs($this->head)->delete("/settings/categories/{$empty->id}")->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $empty->id]);
    }

    public function test_settings_pages_are_closed_to_trainers(): void
    {
        $this->actingAs($this->trainer)->get('/settings')->assertForbidden();
        $this->actingAs($this->trainer)->put('/settings', ['low_stock_threshold' => 1, 'default_borrow_days' => 1, 'site_name' => 'x'])->assertForbidden();
        $this->actingAs($this->trainer)->post('/settings/categories', ['name' => 'x'])->assertForbidden();
    }

    public function test_settings_page_renders(): void
    {
        $this->actingAs($this->keeper)->get('/settings')->assertOk()->assertSee('أجهزة');
    }

    // ---------- بيانات تجريبية ----------

    public function test_demo_seeder_runs_twice_without_duplicating(): void
    {
        $this->seed();
        $counts = [Item::count(), User::count(), \App\Models\Order::count(), Maintenance::count()];

        $this->seed();

        $this->assertSame($counts, [Item::count(), User::count(), \App\Models\Order::count(), Maintenance::count()]);
        $this->assertTrue(User::where('email', 'head@it.edu')->first()->hasRole('super_admin'));
        $this->assertTrue(User::where('email', 'warehouse@it.edu')->first()->hasRole('admin'));
    }
}
