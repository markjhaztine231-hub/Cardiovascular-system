<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin user directory and the user CRUD endpoints expose every account in
 * the clinic, so the requesting user must be an administrator. These tests pin
 * that down for every user endpoint: reading, creating, updating, and deleting.
 */
class AdminUserEndpointAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_the_user_directory(): void
    {
        $this->get(route('admin.users.directory'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_roles_cannot_read_the_user_directory(): void
    {
        $roles = [
            User::factory()->doctor()->create(),
            User::factory()->receptionist()->create(),
            User::factory()->patient()->create(),
        ];

        foreach ($roles as $user) {
            $this->actingAs($user)
                ->get(route('admin.users.directory'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('admin.users.directory', ['search' => 'admin']))
                ->assertForbidden();
        }
    }

    public function test_admin_can_read_the_user_directory(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.directory'))
            ->assertOk();
    }

    public function test_non_admin_cannot_create_update_or_delete_users(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $target = User::factory()->patient()->create(['name' => 'Untouched Patient']);
        $originalEmail = $target->email;

        $this->actingAs($receptionist)
            ->post(route('admin.users.store'), [
                'name' => 'Rogue Admin',
                'email' => 'rogue-admin@example.test',
                'password' => 'Correct-Horse-Battery-9!',
                'role' => 'admin',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'rogue-admin@example.test']);

        $this->actingAs($receptionist)
            ->put(route('admin.users.update', $target), [
                'name' => 'Hijacked',
                'email' => 'hijacked@example.test',
                'role' => 'admin',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Untouched Patient',
            'email' => $originalEmail,
            'role' => 'patient',
        ]);

        $this->actingAs($receptionist)
            ->delete(route('admin.users.delete', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
