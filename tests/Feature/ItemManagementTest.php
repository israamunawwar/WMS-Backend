<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventorySession;
use App\Models\Item;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $head;
    private User $keeper;
    private User $trainer;
    private Category $category;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'trainer'] as $role) {
            Role::create(['name' => $role]);
        }

        $this->head = tap(User::factory()->create(), fn ($u) => $u->assignRole('super_admin'));
        $this->keeper = tap(User::factory()->create(), fn ($u) => $u->assignRole('admin'));
        $this->trainer = tap(User::factory()->create(), fn ($u) => $u->assignRole('trainer'));

        $this->category = Category::create(['name' => 'ملحقات']);
        $this->location = Location::create(['section' => 'مستودع الشبكات', 'cabinet_number' => '3', 'shelf' => 'B']);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'name_en' => 'USB Hub', 'name_ar' => 'موزع USB', 'barcode' => 'USB-1',
            'category_id' => $this->category->id, 'location_id' => $this->location->id, 'initial_balance' => 25,
        ], $override);
    }

    private function item(array $override = []): Item
    {
        return Item::create(array_merge([
            'name_en' => 'Existing', 'category_id' => $this->category->id, 'initial_balance' => 10, 'current_stock' => 10,
        ], $override));
    }

    public function test_admin_can_create_item_and_opening_balance_becomes_current_stock(): void
    {
        $this->actingAs($this->keeper)->post('/items', $this->payload())->assertSessionHas('success');

        $item = Item::where('barcode', 'USB-1')->firstOrFail();
        $this->assertSame(25, $item->initial_balance);
        $this->assertSame(25, $item->current_stock);
        $this->assertSame($this->location->id, $item->location_id);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $this->keeper->id, 'action' => 'item_create']);
    }

    public function test_item_validation(): void
    {
        $this->item(['barcode' => 'DUP']);

        $this->actingAs($this->head)->post('/items', $this->payload(['barcode' => 'DUP']))->assertSessionHasErrors('barcode');
        $this->actingAs($this->head)->post('/items', $this->payload(['name_en' => '']))->assertSessionHasErrors('name_en');
        $this->actingAs($this->head)->post('/items', $this->payload(['category_id' => 999]))->assertSessionHasErrors('category_id');
        $this->actingAs($this->head)->post('/items', $this->payload(['location_id' => 999]))->assertSessionHasErrors('location_id');
        $this->actingAs($this->head)->post('/items', $this->payload(['initial_balance' => -1]))->assertSessionHasErrors('initial_balance');

        $this->assertSame(1, Item::count());
    }

    public function test_item_can_be_created_without_barcode_or_location(): void
    {
        $this->actingAs($this->head)->post('/items', $this->payload(['barcode' => null, 'location_id' => null, 'name_ar' => null]))
            ->assertSessionHas('success');

        $this->assertNull(Item::first()->barcode);
        $this->assertNull(Item::first()->location_id);
    }

    public function test_update_changes_details_but_never_stock(): void
    {
        $item = $this->item(['barcode' => 'OLD']);

        $this->actingAs($this->keeper)->put("/items/{$item->id}", [
            'name_en' => 'Renamed', 'barcode' => 'NEW', 'category_id' => $this->category->id,
            'location_id' => $this->location->id, 'current_stock' => 999, 'initial_balance' => 999,
        ])->assertSessionHas('success');

        $item->refresh();
        $this->assertSame('Renamed', $item->name_en);
        $this->assertSame('NEW', $item->barcode);
        $this->assertSame(10, $item->current_stock);
        $this->assertSame(10, $item->initial_balance);
    }

    public function test_update_allows_keeping_own_barcode_but_not_taking_anothers(): void
    {
        $a = $this->item(['barcode' => 'A']);
        $this->item(['name_en' => 'Other', 'barcode' => 'B']);

        $base = ['name_en' => 'Same', 'category_id' => $this->category->id];

        $this->actingAs($this->head)->put("/items/{$a->id}", $base + ['barcode' => 'A'])->assertSessionHasNoErrors();
        $this->actingAs($this->head)->put("/items/{$a->id}", $base + ['barcode' => 'B'])->assertSessionHasErrors('barcode');
    }

    public function test_restock_increases_current_and_opening_balance(): void
    {
        $item = $this->item();

        $this->actingAs($this->keeper)->post("/items/{$item->id}/restock", ['quantity' => 15, 'note' => 'فاتورة 12'])
            ->assertSessionHas('success');

        $item->refresh();
        $this->assertSame(25, $item->current_stock);
        $this->assertSame(25, $item->initial_balance); // التوريد ليس "استهلاكاً" سالباً
        $this->assertDatabaseHas('activity_logs', ['action' => 'item_restock']);

        $this->actingAs($this->keeper)->post("/items/{$item->id}/restock", ['quantity' => 0])->assertSessionHasErrors('quantity');
        $this->assertSame(25, $item->fresh()->current_stock);
    }

    public function test_item_without_history_can_be_deleted(): void
    {
        $item = $this->item();

        $this->actingAs($this->keeper)->delete("/items/{$item->id}")->assertSessionHas('success');

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'item_delete']);
    }

    public function test_item_with_history_cannot_be_deleted(): void
    {
        $ordered = $this->item(['name_en' => 'Ordered']);
        Order::create(['user_id' => $this->trainer->id, 'status' => Order::NEW])->items()->create(['item_id' => $ordered->id, 'quantity' => 1]);

        $broken = $this->item(['name_en' => 'Broken']);
        Maintenance::create(['item_id' => $broken->id, 'description' => 'x', 'status' => Maintenance::PENDING]);

        $counted = $this->item(['name_en' => 'Counted']);
        InventorySession::create(['title' => 's', 'created_by' => $this->head->id, 'status' => InventorySession::OPEN])
            ->inventorySessionItems()->create(['item_id' => $counted->id, 'system_quantity' => 10, 'difference' => 0]);

        foreach ([$ordered, $broken, $counted] as $item) {
            $this->actingAs($this->head)->delete("/items/{$item->id}")->assertSessionHas('error');
            $this->assertDatabaseHas('items', ['id' => $item->id]);
        }
    }

    public function test_trainer_and_guest_cannot_manage_items_but_can_view(): void
    {
        $item = $this->item();

        $this->actingAs($this->trainer)->post('/items', $this->payload())->assertForbidden();
        $this->actingAs($this->trainer)->put("/items/{$item->id}", ['name_en' => 'x', 'category_id' => $this->category->id])->assertForbidden();
        $this->actingAs($this->trainer)->post("/items/{$item->id}/restock", ['quantity' => 5])->assertForbidden();
        $this->actingAs($this->trainer)->delete("/items/{$item->id}")->assertForbidden();
        $this->assertSame(10, $item->fresh()->current_stock);

        $this->actingAs($this->trainer)->get('/items')->assertOk()->assertSee('Existing')->assertDontSee('إضافة مادة');
        $this->actingAs($this->keeper)->get('/items')->assertOk()->assertSee('إضافة مادة');

        auth()->logout();
        $this->post('/items', $this->payload())->assertRedirect('/login');
    }

    public function test_search_category_filter_and_pagination(): void
    {
        $other = Category::create(['name' => 'شبكات']);
        $this->item(['name_en' => 'Alpha Mouse', 'name_ar' => 'ماوس-ألفا', 'barcode' => 'ZZ-1']);
        $this->item(['name_en' => 'Beta Router', 'category_id' => $other->id]);

        $this->actingAs($this->head)->get('/items?search=alpha')->assertSee('Alpha Mouse')->assertDontSee('Beta Router');
        $this->actingAs($this->head)->get('/items?search=ZZ-1')->assertSee('Alpha Mouse')->assertDontSee('Beta Router');
        $this->actingAs($this->head)->get('/items?search='.urlencode('ألفا'))->assertSee('Alpha Mouse')->assertDontSee('Beta Router');
        $this->actingAs($this->head)->get("/items?category={$other->id}")->assertSee('Beta Router')->assertDontSee('Alpha Mouse');

        for ($i = 1; $i <= 25; $i++) {
            $this->item(['name_en' => "Bulk {$i}"]);
        }
        $this->assertSame(20, $this->actingAs($this->head)->get('/items')->viewData('items')->count());
    }

    public function test_exports_respect_search_filter(): void
    {
        $this->item(['name_en' => 'Alpha Mouse']);
        $this->item(['name_en' => 'Beta Router']);

        $response = $this->actingAs($this->head)->get('/items/export/pdf?search=Alpha');
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->actingAs($this->head)->get('/items/export/excel?search=Alpha')->assertOk();
    }

    public function test_locations_can_be_added_and_only_empty_ones_deleted(): void
    {
        $this->actingAs($this->head)->post('/settings/locations', ['section' => 'مستودع الحواسيب', 'cabinet_number' => '1'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('locations', ['section' => 'مستودع الحواسيب', 'shelf' => null]);

        $this->actingAs($this->head)->post('/settings/locations', ['section' => '', 'cabinet_number' => ''])
            ->assertSessionHasErrors(['section', 'cabinet_number']);

        $this->item(['location_id' => $this->location->id]);
        $this->actingAs($this->head)->delete("/settings/locations/{$this->location->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('locations', ['id' => $this->location->id]);

        $empty = Location::where('section', 'مستودع الحواسيب')->first();
        $this->actingAs($this->head)->delete("/settings/locations/{$empty->id}")->assertSessionHas('success');
        $this->assertDatabaseMissing('locations', ['id' => $empty->id]);

        $this->actingAs($this->trainer)->post('/settings/locations', ['section' => 'x', 'cabinet_number' => '1'])->assertForbidden();
    }

    public function test_location_label_is_shown_on_items_page(): void
    {
        $this->item(['location_id' => $this->location->id]);

        $this->actingAs($this->head)->get('/items')->assertSee('مستودع الشبكات - خزانة 3 - رف B');
        $this->actingAs($this->head)->get('/settings')->assertOk()->assertSee('مستودع الشبكات');
    }
}
