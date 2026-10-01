<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $head;
    private User $keeper;
    private User $trainer;
    private User $otherTrainer;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'trainer'] as $role) {
            Role::create(['name' => $role]);
        }

        $this->head = tap(User::factory()->create(), fn ($u) => $u->assignRole('super_admin'));
        $this->keeper = tap(User::factory()->create(), fn ($u) => $u->assignRole('admin'));
        $this->trainer = tap(User::factory()->create(['name' => 'مدرب أول']), fn ($u) => $u->assignRole('trainer'));
        $this->otherTrainer = tap(User::factory()->create(['name' => 'مدرب ثان']), fn ($u) => $u->assignRole('trainer'));
    }

    private function order(User $user, string $status = Order::NEW, array $extra = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id, 'destination' => 'مخبر الشبكات', 'priority' => 'عادي', 'status' => $status,
        ], $extra));
    }

    public function test_approval_saves_status_notes_and_activity_log(): void
    {
        $order = $this->order($this->trainer);

        $this->actingAs($this->head)->patch("/orders/{$order->id}/status", [
            'action' => 'موافقة', 'notes' => 'لمشروع التخرج',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(Order::APPROVED, $order->status);
        $this->assertSame('لمشروع التخرج', $order->notes);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $this->head->id, 'action' => 'approve_order']);
    }

    public function test_rejection_requires_notes(): void
    {
        $order = $this->order($this->trainer);

        $this->actingAs($this->head)->patch("/orders/{$order->id}/status", ['action' => 'رفض'])
            ->assertSessionHasErrors('notes');

        $this->assertSame(Order::NEW, $order->fresh()->status);
    }

    public function test_decided_order_cannot_be_decided_again(): void
    {
        $order = $this->order($this->trainer, Order::REJECTED, ['notes' => 'سبب']);

        $this->actingAs($this->head)->patch("/orders/{$order->id}/status", [
            'action' => 'موافقة', 'notes' => 'تراجع',
        ])->assertSessionHas('error');

        $this->assertSame(Order::REJECTED, $order->fresh()->status);
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_trainer_cannot_decide_orders(): void
    {
        $order = $this->order($this->trainer);

        $this->actingAs($this->trainer)->patch("/orders/{$order->id}/status", [
            'action' => 'موافقة', 'notes' => 'x',
        ])->assertForbidden();

        $this->assertSame(Order::NEW, $order->fresh()->status);
    }

    public function test_trainer_sees_only_own_orders_while_admins_see_all(): void
    {
        $mine = $this->order($this->trainer, Order::NEW, ['destination' => 'وجهتي']);
        $theirs = $this->order($this->otherTrainer, Order::NEW, ['destination' => 'وجهة الغير']);

        $this->actingAs($this->trainer)->get('/orders')->assertOk()
            ->assertSee('وجهتي')->assertDontSee('وجهة الغير');

        $this->actingAs($this->keeper)->get('/orders')->assertOk()
            ->assertSee('وجهتي')->assertSee('وجهة الغير');
    }

    public function test_search_by_order_number_and_status_filter(): void
    {
        $a = $this->order($this->trainer, Order::NEW, ['destination' => 'وجهة-أ']);
        $b = $this->order($this->trainer, Order::REJECTED, ['destination' => 'وجهة-ب']);

        $this->actingAs($this->head)->get("/orders?search=ORD-{$b->id}")->assertOk()
            ->assertSee('وجهة-ب')->assertDontSee('وجهة-أ');

        $this->actingAs($this->head)->get('/orders?status='.urlencode(Order::PENDING))->assertOk()
            ->assertSee('وجهة-أ')->assertDontSee('وجهة-ب');

        $this->actingAs($this->head)->get('/orders?filter=rejected')->assertOk()
            ->assertSee('وجهة-ب')->assertDontSee('وجهة-أ');
    }

    public function test_dashboard_shows_real_statistics(): void
    {
        $category = Category::create(['name' => 'أجهزة']);
        $normal = Item::create(['name_en' => 'Mouse', 'name_ar' => 'ماوس', 'category_id' => $category->id, 'initial_balance' => 50, 'current_stock' => 20]);
        $low = Item::create(['name_en' => 'Cable', 'category_id' => $category->id, 'initial_balance' => 10, 'current_stock' => 3]);
        Item::create(['name_en' => 'Empty', 'category_id' => $category->id, 'initial_balance' => 5, 'current_stock' => 0]);
        Maintenance::create(['item_id' => $low->id, 'description' => 'x', 'status' => 'repairing']);
        Maintenance::create(['item_id' => $normal->id, 'description' => 'y', 'status' => 'fixed']);

        $this->order($this->trainer, Order::NEW);
        $this->order($this->trainer, Order::PENDING);
        $this->order($this->trainer, Order::REJECTED);
        $this->order($this->otherTrainer, Order::APPROVED, ['destination' => 'مخبر آخر']);
        $old = $this->order($this->trainer, Order::NEW);
        $old->forceFill(['created_at' => now()->subYear()])->save();

        $response = $this->actingAs($this->head)->get('/dashboard')->assertOk();

        $this->assertSame(3, $response->viewData('totalItems'));
        $this->assertSame(1, $response->viewData('lowStockItems'));
        $this->assertSame(1, $response->viewData('missingItems'));
        $this->assertSame(1, $response->viewData('damagedItems'));
        $this->assertSame(3, $response->viewData('pendingOrders'));
        $this->assertSame(1, $response->viewData('rejectedOrders'));
        $this->assertSame(4, $response->viewData('todayOrders'));
        $this->assertSame(4, $response->viewData('monthOrders'));
        $this->assertSame('ماوس', $response->viewData('mostUsedItemName'));
        $this->assertSame('مخبر الشبكات', $response->viewData('topLab'));
        $this->assertSame('مدرب أول', $response->viewData('topTrainer'));
    }

    public function test_item_filters_match_dashboard_definitions(): void
    {
        $category = Category::create(['name' => 'أجهزة']);
        $low = Item::create(['name_en' => 'LowItem', 'category_id' => $category->id, 'current_stock' => 3]);
        $empty = Item::create(['name_en' => 'EmptyItem', 'category_id' => $category->id, 'current_stock' => 0]);
        $broken = Item::create(['name_en' => 'BrokenItem', 'category_id' => $category->id, 'current_stock' => 50]);
        Maintenance::create(['item_id' => $broken->id, 'description' => 'z', 'status' => 'pending']);

        $this->actingAs($this->head)->get('/items?type=low_stock')
            ->assertSee('LowItem')->assertDontSee('EmptyItem')->assertDontSee('BrokenItem');
        $this->actingAs($this->head)->get('/items?type=missing')
            ->assertSee('EmptyItem')->assertDontSee('LowItem');
        $this->actingAs($this->head)->get('/items?type=damaged')
            ->assertSee('BrokenItem')->assertDontSee('LowItem');
    }
}
