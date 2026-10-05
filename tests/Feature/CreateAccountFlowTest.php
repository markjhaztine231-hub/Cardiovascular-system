<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_user_persists_and_appears_in_directory()
    {
        $admin = User::factory()->role('admin')->create(['email' => 'adminz@cardiocare.test']);

        // POST valid account creation
        $resp = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Michael Chen',
            'email' => 'michael.chen@clinic.test',
            'password' => 'Str0ng!Password2026',
            'role' => 'doctor',
            'specialty' => 'Interventional Cardiology',
        ]);
        $resp->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'michael.chen@clinic.test', 'role' => 'doctor']);
        $this->assertDatabaseHas('providers', ['user_id' => User::where('email', 'michael.chen@clinic.test')->value('id')]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Created new DOCTOR account for michael.chen@clinic.test from IP: 127.0.0.1']);

        // Appears in directory
        $this->actingAs($admin)->get(route('admin.users.directory'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UsersDirectory')
                ->where('users.total', 2));

        $this->actingAs($admin)->get(route('admin.users.directory', ['search' => 'Michael Chen']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Dr. Michael Chen'));
    }

    public function test_store_user_rejects_weak_password_with_validation_errors()
    {
        $admin = User::factory()->role('admin')->create(['email' => 'adminy@cardiocare.test']);

        $resp = $this->from(route('admin.create-account'))->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@clinic.test',
            'password' => 'password',
            'role' => 'receptionist',
        ]);

        $resp->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['email' => 'jane@clinic.test']);
    }

    public function test_store_duplicate_email_renders_error()
    {
        $admin = User::factory()->role('admin')->create(['email' => 'adminx@cardiocare.test']);
        User::factory()->role('patient')->create(['email' => 'dup@clinic.test']);

        $this->from(route('admin.create-account'))->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Dup User',
            'email' => 'dup@clinic.test',
            'password' => 'Str0ng!Password2026',
            'role' => 'receptionist',
        ])->assertSessionHasErrors(['email']);
    }
}
