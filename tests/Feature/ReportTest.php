<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\Category;
use App\Models\InventorySession;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $keeper;
    private User $trainer;
    private Item $mouse;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'trainer'] as $role) {
            Role::create(['name' => $role]);
        }

        $this->keeper = tap(User::factory()->create(['name' => 'أمين']), fn ($u) => $u->assignRole('admin'));
        $this->trainer = tap(User::factory()->create(['name' => 'مدرب-س']), fn ($u) => $u->assignRole('trainer'));

        $category = Category::create(['name' => 'ملحقات']);
        $this->mouse = Item::create(['name_en' => 'Mouse', 'name_ar' => 'ماوس-تقرير', 'barcode' => 'IT-9', 'category_id' => $category->id, 'initial_balance' => 10, 'current_stock' => 7]);

        // طلب معتمد قديم وطلب معتمد حديث
        $old = Order::create(['user_id' => $this->trainer->id, 'destination' => 'وجهة-قديمة', 'priority' => 'عادي', 'status' => Order::APPROVED]);
        $old->items()->create(['item_id' => $this->mouse->id, 'quantity' => 2]);
        $old->forceFill(['created_at' => '2026-01-10 10:00:00'])->save();

        $new = Order::create(['user_id' => $this->trainer->id, 'destination' => 'وجهة-حديثة', 'priority' => 'حساس', 'status' => Order::APPROVED]);
        $new->items()->create(['item_id' => $this->mouse->id, 'quantity' => 5]);
        $new->forceFill(['created_at' => '2026-03-10 10:00:00'])->save();

        // طلب مرفوض لا يدخل في العهدة
        Order::create(['user_id' => $this->trainer->id, 'destination' => 'وجهة-مرفوضة', 'priority' => 'عادي', 'status' => Order::REJECTED])
            ->forceFill(['created_at' => '2026-03-11 10:00:00'])->save();

        Maintenance::create(['item_id' => $this->mouse->id, 'description' => 'عطل-قائم', 'status' => Maintenance::PENDING]);
        Maintenance::create(['item_id' => $this->mouse->id, 'description' => 'عطل-مصلح', 'status' => Maintenance::FIXED, 'cost' => 500]);

        InventorySession::create(['title' => 'جرد-تقرير', 'created_by' => $this->keeper->id, 'status' => InventorySession::APPROVED]);
    }

    private function preview(string $query = ''): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->keeper)->get('/reports?'.$query)->assertOk();
    }

    public function test_page_renders_without_a_selected_report(): void
    {
        $this->preview()->assertSee('اختر نوع التقرير');
    }

    public function test_every_report_type_previews(): void
    {
        $this->preview('type=inventory')->assertSee('ماوس-تقرير')->assertSee('IT-9');
        $this->preview('type=orders')->assertSee('وجهة-قديمة')->assertSee('وجهة-حديثة')->assertSee('ماوس-تقرير × 2');
        $this->preview('type=inventory_sessions')->assertSee('جرد-تقرير')->assertSee('معتمد');
        $this->preview('type=damaged')->assertSee('عطل-قائم')->assertDontSee('عطل-مصلح');
        $this->preview('type=maintenance')->assertSee('عطل-قائم')->assertSee('عطل-مصلح');
        $this->preview('type=custody')->assertSee('مدرب-س')->assertDontSee('وجهة-مرفوضة');
    }

    public function test_date_range_filters_rows(): void
    {
        $this->preview('type=orders&from=2026-03-01&to=2026-03-31')
            ->assertSee('وجهة-حديثة')->assertDontSee('وجهة-قديمة');

        $this->preview('type=orders&to=2026-02-01')
            ->assertSee('وجهة-قديمة')->assertDontSee('وجهة-حديثة');

        // العهدة تحسب فقط الطلبات المعتمدة ضمن الفترة: 5 قطع فقط
        $response = $this->preview('type=custody&from=2026-03-01&to=2026-03-31');
        $this->assertCount(1, $response->viewData('report')['rows']);
        $this->assertSame(5, $response->viewData('report')['rows'][0][3]);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->keeper)->get('/reports?type=bogus')->assertSessionHasErrors('type');
        $this->actingAs($this->keeper)->get('/reports?type=orders&from=2026-05-01&to=2026-01-01')->assertSessionHasErrors('to');
        $this->actingAs($this->keeper)->get('/reports/export?format=pdf')->assertSessionHasErrors('type');
        $this->actingAs($this->keeper)->get('/reports/export?type=orders&format=docx')->assertSessionHasErrors('format');
    }

    public function test_every_report_exports_as_pdf_and_excel(): void
    {
        foreach (array_keys(ReportController::TYPES) as $type) {
            $pdf = $this->actingAs($this->keeper)->get("/reports/export?type={$type}&format=pdf");
            $pdf->assertOk();
            $this->assertStringStartsWith('%PDF', $pdf->getContent(), "PDF for {$type}");

            $excel = $this->actingAs($this->keeper)->get("/reports/export?type={$type}&format=excel");
            $excel->assertOk();
            $this->assertStringContainsString('.xlsx', $excel->headers->get('Content-Disposition'), "Excel for {$type}");
        }
    }

    public function test_reports_are_closed_to_trainers_and_guests(): void
    {
        $this->actingAs($this->trainer)->get('/reports')->assertForbidden();
        $this->actingAs($this->trainer)->get('/reports/export?type=orders&format=pdf')->assertForbidden();

        auth()->logout();
        $this->get('/reports/export?type=orders&format=pdf')->assertRedirect('/login');
    }
}
