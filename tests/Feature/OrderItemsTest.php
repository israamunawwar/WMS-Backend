<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderItemsTest extends TestCase
{
    use RefreshDatabase;

    private User $head;
    private User $trainer;
    private Item $mouse;
    private Item $cable;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'trainer'] as $role) {
            Role::create(['name' => $role]);
        }

        $this->head = tap(User::factory()->create(), fn ($u) => $u->assignRole('super_admin'));
        $this->trainer = tap(User::factory()->create(), fn ($u) => $u->assignRole('trainer'));

        $category = Category::create(['name' => 'أجهزة']);
        $this->mouse = Item::create(['name_en' => 'Mouse', 'name_ar' => 'ماوس', 'category_id' => $category->id, 'initial_balance' => 10, 'current_stock' => 10]);
        $this->cable = Item::create(['name_en' => 'Cable', 'name_ar' => 'كابل', 'category_id' => $category->id, 'initial_balance' => 4, 'current_stock' => 4]);
    }

    private function payload(array $items, array $extra = []): array
    {
        return array_merge(['destination' => 'مخبر الشبكات', 'priority' => 'عادي', 'items' => $items], $extra);
    }

    private function newOrder(array $lines): Order
    {
        $this->actingAs($this->trainer)->post('/orders', $this->payload($lines))->assertSessionHas('success');

        return Order::latest('id')->firstOrFail();
    }

    public function test_trainer_can_create_order_with_items(): void
    {
        $order = $this->newOrder([
            ['item_id' => $this->mouse->id, 'quantity' => 3],
            ['item_id' => $this->cable->id, 'quantity' => 2],
        ]);

        $this->assertSame($this->trainer->id, $order->user_id);
        $this->assertSame(Order::NEW, $order->status);
        $this->assertSame(2, $order->items()->count());
        $this->assertDatabaseHas('activity_logs', ['user_id' => $this->trainer->id, 'action' => 'create_order']);
        // إنشاء الطلب لا يمس المخزون
        $this->assertSame(10, $this->mouse->fresh()->current_stock);
    }

    public function test_order_requires_valid_items(): void
    {
        $this->actingAs($this->trainer)->post('/orders', $this->payload([]))->assertSessionHasErrors('items');
        $this->actingAs($this->trainer)->post('/orders', $this->payload([['item_id' => 999, 'quantity' => 1]]))->assertSessionHasErrors('items.0.item_id');
        $this->actingAs($this->trainer)->post('/orders', $this->payload([['item_id' => $this->mouse->id, 'quantity' => 0]]))->assertSessionHasErrors('items.0.quantity');
        $this->actingAs($this->trainer)->post('/orders', $this->payload([
            ['item_id' => $this->mouse->id, 'quantity' => 1],
            ['item_id' => $this->mouse->id, 'quantity' => 2],
        ]))->assertSessionHasErrors('items.0.item_id');
        $this->actingAs($this->trainer)->post('/orders', $this->payload([['item_id' => $this->mouse->id, 'quantity' => 1]], ['priority' => 'x']))->assertSessionHasErrors('priority');

        $this->assertSame(0, Order::count());
    }

    public function test_guest_cannot_create_order(): void
    {
        $this->post('/orders', $this->payload([['item_id' => $this->mouse->id, 'quantity' => 1]]))->assertRedirect('/login');
    }

    public function test_order_items_are_shown_in_the_orders_page(): void
    {
        $this->newOrder([['item_id' => $this->mouse->id, 'quantity' => 3]]);

        $this->actingAs($this->head)->get('/orders')->assertOk()->assertSee('ماوس');
    }

    public function test_approval_deducts_stock(): void
    {
        $order = $this->newOrder([
            ['item_id' => $this->mouse->id, 'quantity' => 3],
            ['item_id' => $this->cable->id, 'quantity' => 4],
        ]);

        $this->actingAs($this->head)->patch("/orders/{$order->id}/status", ['action' => 'موافقة', 'notes' => 'تمام'])
            ->assertSessionHas('success');

        $this->assertSame(Order::APPROVED, $order->fresh()->status);
        $this->assertSame(7, $this->mouse->fresh()->current_stock);
        $this->assertSame(0, $this->cable->fresh()->current_stock);
    }

    public function test_approval_is_blocked_when_stock_is_insufficient_and_nothing_is_deducted(): void
    {
        // الأول متوفر، والثاني (5 من أصل 4) غير كافٍ
        $order = $this->newOrder([
            ['item_id' => $this->mouse->id, 'quantity' => 3],
            ['item_id' => $this->cable->id, 'quantity' => 5],
        ]);

        $this->actingAs($this->head)->patch("/orders/{$order->id}/status", ['action' => 'موافقة', 'notes' => 'x'])
            ->assertSessionHas('error');

        $this->assertSame(Order::NEW, $order->fresh()->status);
        $this->assertSame(10, $this->mouse->fresh()->current_stock);
        $this->assertSame(4, $this->cable->fresh()->current_stock);
    }

    public function test_rejection_does_not_touch_stock(): void
    {
        $order = $this->newOrder([['item_id' => $this->mouse->id, 'quantity' => 3]]);

        $this->actingAs($this->head)->patch("/orders/{$order->id}/status", ['action' => 'رفض', 'notes' => 'غير مبرر'])
            ->assertSessionHas('success');

        $this->assertSame(Order::REJECTED, $order->fresh()->status);
        $this->assertSame(10, $this->mouse->fresh()->current_stock);
    }

    public function test_approving_twice_does_not_deduct_twice(): void
    {
        $order = $this->newOrder([['item_id' => $this->mouse->id, 'quantity' => 3]]);

        $this->actingAs($this->head)->patch("/orders/{$order->id}/status", ['action' => 'موافقة', 'notes' => 'a']);
        $this->actingAs($this->head)->patch("/orders/{$order->id}/status", ['action' => 'موافقة', 'notes' => 'b'])
            ->assertSessionHas('error');

        $this->assertSame(7, $this->mouse->fresh()->current_stock);
    }
}
