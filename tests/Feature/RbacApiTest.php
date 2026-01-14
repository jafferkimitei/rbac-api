<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RbacApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'manage users']);

        $super = Role::create(['name' => 'super_admin']);
        $admin = Role::create(['name' => 'admin']);

        $super->syncPermissions(['view users', 'manage users']);
        $admin->syncPermissions(['view users']);
    }

    private function makeUser(string $email, string $roleName): User
    {
        $u = User::factory()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        $u->assignRole($roleName);

        return $u;
    }

    public function test_login_returns_expected_payload_shape(): void
    {
        $user = $this->makeUser('admin@example.com', 'admin');

        $res = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $res->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'roles', 'permissions'],
            ]);

        $this->assertIsString($res->json('token'));
        $this->assertIsArray($res->json('user.roles'));
        $this->assertIsArray($res->json('user.permissions'));
    }

    public function test_me_requires_auth(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_returns_roles_and_permissions(): void
    {
        $user = $this->makeUser('admin@example.com', 'admin');
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'email', 'roles', 'permissions'])
            ->assertJsonPath('email', $user->email);

        $this->assertIsArray($this->getJson('/api/me')->json('roles'));
        $this->assertIsArray($this->getJson('/api/me')->json('permissions'));
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = $this->makeUser('admin@example.com', 'admin');

        $loginRes = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $token = $loginRes->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_admin_users_endpoint_denies_unauthenticated(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
    }

    public function test_admin_users_endpoint_denies_admin_without_manage_users(): void
    {
        $user = $this->makeUser('admin2@example.com', 'admin');
        Sanctum::actingAs($user);

        $this->assertFalse($user->can('manage users'));

        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_admin_users_endpoint_allows_super_admin(): void
    {
        $user = $this->makeUser('super@example.com', 'super_admin');
        Sanctum::actingAs($user);

        $this->assertTrue($user->can('manage users'));

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure([
                'ok',
                'data' => [
                    '*' => ['id', 'name', 'email', 'roles'],
                ],
            ])
            ->assertJsonPath('ok', true);
    }
}
