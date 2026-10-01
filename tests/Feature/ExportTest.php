<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'trainer']);
        $this->trainer = tap(User::factory()->create(), fn ($u) => $u->assignRole('trainer'));

        $category = Category::create(['name' => 'أجهزة']);
        Item::create(['name_en' => 'Monitor', 'name_ar' => 'شاشة ديل', 'category_id' => $category->id, 'initial_balance' => 10, 'current_stock' => 4]);
    }

    public function test_pdf_export_returns_a_real_pdf_file(): void
    {
        $response = $this->actingAs($this->trainer)->get('/items/export/pdf?type=all');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        if ($path = getenv('WMS_PDF_DUMP')) {
            file_put_contents($path, $response->getContent());
        }
    }

    public function test_excel_export_downloads_a_spreadsheet(): void
    {
        $response = $this->actingAs($this->trainer)->get('/items/export/excel?type=all');

        $response->assertOk();
        $this->assertStringContainsString('inventory_report.xlsx', $response->headers->get('Content-Disposition'));
    }
}
