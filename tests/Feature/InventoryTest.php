<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventorySession;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $head;
    private User $keeper;
    private Item $monitor;
    private Item $cable;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'trainer'] as $role) {
            Role::create(['name' => $role]);
        }

        $this->head = tap(User::factory()->create(), fn ($u) => $u->assignRole('super_admin'));
        $this->keeper = tap(User::factory()->create(), fn ($u) => $u->assignRole('admin'));

        $category = Category::create(['name' => 'أجهزة']);
        $this->monitor = Item::create(['name_en' => 'Monitor', 'category_id' => $category->id, 'initial_balance' => 10, 'current_stock' => 10]);
        $this->cable = Item::create(['name_en' => 'Cable', 'category_id' => $category->id, 'initial_balance' => 20, 'current_stock' => 20]);
    }

    private function beginInventory(): InventorySession
    {
        $this->actingAs($this->keeper)->post('/inventory', ['name' => 'جرد 2026'])->assertSessionHas('success');

        return InventorySession::firstOrFail();
    }

    private function recordCounts(InventorySession $session, int $monitorQty, int $cableQty): void
    {
        $rows = $session->inventorySessionItems;

        $this->actingAs($this->keeper)->post('/inventory/count', ['counts' => [
            $rows->firstWhere('item_id', $this->monitor->id)->id => $monitorQty,
            $rows->firstWhere('item_id', $this->cable->id)->id => $cableQty,
        ]])->assertSessionHas('success');
    }

    public function test_starting_a_session_snapshots_current_stock(): void
    {
        $session = $this->beginInventory();

        $this->assertSame(InventorySession::OPEN, $session->status);
        $this->assertSame($this->keeper->id, $session->created_by);
        $this->assertSame(2, $session->inventorySessionItems()->count());
        $this->assertSame(10, $session->inventorySessionItems()->where('item_id', $this->monitor->id)->value('system_quantity'));
    }

    public function test_only_one_active_session_at_a_time(): void
    {
        $this->beginInventory();

        $this->actingAs($this->keeper)->post('/inventory', ['name' => 'ثانية'])->assertSessionHas('error');
        $this->assertSame(1, InventorySession::count());
    }

    public function test_counting_records_differences(): void
    {
        $session = $this->beginInventory();
        $this->recordCounts($session, 8, 23);

        $rows = $session->fresh()->inventorySessionItems;
        $this->assertSame(-2, $rows->firstWhere('item_id', $this->monitor->id)->difference);
        $this->assertSame(3, $rows->firstWhere('item_id', $this->cable->id)->difference);
        // الأرصدة لا تتغير قبل التسوية
        $this->assertSame(10, $this->monitor->fresh()->current_stock);
    }

    public function test_cannot_resolve_before_all_items_are_counted(): void
    {
        $this->beginInventory();

        $this->actingAs($this->head)->post('/inventory/resolve', ['decision' => 'x'])->assertSessionHas('error');
        $this->assertSame(InventorySession::OPEN, InventorySession::first()->status);
    }

    public function test_full_cycle_resolve_then_approve(): void
    {
        $session = $this->beginInventory();
        $this->recordCounts($session, 8, 23);

        $this->actingAs($this->head)->post('/inventory/resolve', ['decision' => 'تحميل المسؤولية'])->assertSessionHas('success');

        $this->assertSame(8, $this->monitor->fresh()->current_stock);
        $this->assertSame(23, $this->cable->fresh()->current_stock);
        $this->assertSame(InventorySession::COMPLETED, $session->fresh()->status);

        $this->actingAs($this->head)->post('/inventory/close')->assertSessionHas('success');

        $session->refresh();
        $this->assertSame(InventorySession::APPROVED, $session->status);
        $this->assertSame($this->head->id, $session->approved_by);
        // الرصيد المُعتمد يصبح رصيداً افتتاحياً للعام القادم
        $this->assertSame(8, $this->monitor->fresh()->initial_balance);
        $this->assertSame(23, $this->cable->fresh()->initial_balance);
    }

    public function test_cannot_approve_before_resolving(): void
    {
        $this->beginInventory();

        $this->actingAs($this->head)->post('/inventory/close')->assertSessionHas('error');
        $this->assertSame(InventorySession::OPEN, InventorySession::first()->status);
    }

    public function test_warehouse_keeper_cannot_resolve_or_approve(): void
    {
        $this->beginInventory();

        $this->actingAs($this->keeper)->post('/inventory/resolve', ['decision' => 'x'])->assertForbidden();
        $this->actingAs($this->keeper)->post('/inventory/close')->assertForbidden();
    }

    public function test_inventory_page_renders_for_keeper_and_head(): void
    {
        $this->actingAs($this->keeper)->get('/inventory')->assertOk();

        $this->beginInventory();

        $this->actingAs($this->keeper)->get('/inventory')->assertOk()->assertSee('جرد 2026');
        $this->actingAs($this->head)->get('/inventory')->assertOk()->assertSee('معالجة الفروقات');
    }
}
