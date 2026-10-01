<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'trainer'] as $role) {
            Role::create(['name' => $role]);
        }
    }

    private function userWithRole(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role));
    }

    public function test_guests_are_redirected_to_login_on_protected_routes(): void
    {
        foreach (['/items', '/items/export/excel', '/items/export/pdf', '/categories', '/orders', '/users'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_public_registration_is_closed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'X', 'email' => 'x@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertStatus(404);
    }

    public function test_trainer_cannot_manage_users(): void
    {
        $trainer = $this->userWithRole('trainer');
        $target = $this->userWithRole('trainer');

        $this->actingAs($trainer)->get('/users')->assertForbidden();
        $this->actingAs($trainer)->patch("/users/{$trainer->id}/role", ['role' => 'super_admin'])->assertForbidden();
        $this->actingAs($trainer)->delete("/users/{$target->id}")->assertForbidden();

        $this->assertTrue($trainer->fresh()->hasRole('trainer'));
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_warehouse_admin_cannot_manage_users(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get('/users')->assertForbidden();
        $this->actingAs($admin)->patch("/users/{$admin->id}/role", ['role' => 'super_admin'])->assertForbidden();
    }

    public function test_trainer_cannot_open_admin_pages_or_approve_orders(): void
    {
        $trainer = $this->userWithRole('trainer');

        foreach (['/inventory', '/maintenance', '/reports', '/logs', '/settings'] as $url) {
            $this->actingAs($trainer)->get($url)->assertForbidden();
        }
    }

    public function test_super_admin_can_manage_users(): void
    {
        $head = $this->userWithRole('super_admin');
        $target = $this->userWithRole('trainer');

        $this->actingAs($head)->get('/users')->assertOk();
        $this->actingAs($head)->patch("/users/{$target->id}/role", ['role' => 'admin'])->assertSessionHas('success');

        $this->assertTrue($target->fresh()->hasRole('admin'));
    }

    public function test_last_super_admin_cannot_be_demoted_or_deleted(): void
    {
        $head = $this->userWithRole('super_admin');

        $this->actingAs($head)->patch("/users/{$head->id}/role", ['role' => 'trainer'])->assertSessionHas('error');
        $this->assertTrue($head->fresh()->hasRole('super_admin'));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
