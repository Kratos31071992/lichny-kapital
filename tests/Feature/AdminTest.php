<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Админка (п. 5 ТЗ): доступ только для админов, список пользователей,
 * блокировка, удаление, массовые действия, журнал действий.
 */
class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_guests_cannot_access_admin(): void
    {
        $this->get('/admin/users')->assertRedirect(route('login', absolute: false));
    }

    public function test_regular_users_cannot_access_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_sees_users_list(): void
    {
        User::factory()->count(3)->create();

        $response = $this->actingAs($this->admin())->get('/admin/users');

        $response->assertOk();
    }

    public function test_admin_can_block_and_unblock_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)->post("/admin/users/{$user->id}/block", ['blocked' => true]);
        $this->assertTrue($user->fresh()->is_blocked);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/block", ['blocked' => false]);
        $this->assertFalse($user->fresh()->is_blocked);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)->delete("/admin/users/{$user->id}");

        $this->assertNull($user->fresh());
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

        $this->assertNotNull($admin->fresh());
    }

    public function test_bulk_block_writes_audit_log(): void
    {
        $admin = $this->admin();
        $users = User::factory()->count(3)->create();

        $this->actingAs($admin)->post('/admin/users/bulk', [
            'ids' => $users->pluck('id')->all(),
            'action' => 'block',
        ]);

        $this->assertTrue($users->first()->fresh()->is_blocked);
        $this->assertSame(3, AuditLog::where('action', 'user.block')->count());
        // Каждая запись привязана к админу, совершившему действие.
        $this->assertSame(3, AuditLog::where('admin_id', $admin->id)->count());
    }

    public function test_audit_log_page_is_available_for_admin(): void
    {
        $admin = $this->admin();
        AuditLog::factory()->count(5)->create(['admin_id' => $admin->id]);

        $response = $this->actingAs($admin)->get('/admin/logs');

        $response->assertOk();
    }
}
