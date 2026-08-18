<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sso.mock' => true]);
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('เข้าสู่ระบบจัดการข้อมูล');
    }

    public function test_mock_admin_can_login_and_view_users(): void
    {
        $response = $this->post('/admin/login', [
            'email' => config('sso.mock_email'),
            'password' => config('sso.mock_password'),
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertAuthenticated();
        $this->get('/admin/users')->assertOk()->assertSee('จัดการผู้ใช้');
    }

    public function test_admin_can_update_another_user(): void
    {
        $this->post('/admin/login', [
            'email' => config('sso.mock_email'),
            'password' => config('sso.mock_password'),
        ])->assertRedirect('/admin/users');

        $user = User::factory()->create();

        $this->put("/admin/users/{$user->id}", [
            'code' => $user->code,
            'email' => $user->email,
            'prefix_th' => $user->prefix_th,
            'first_name_th' => 'ชื่อใหม่',
            'last_name_th' => $user->last_name_th,
            'prefix_en' => $user->prefix_en,
            'first_name_en' => $user->first_name_en,
            'last_name_en' => $user->last_name_en,
            'status' => $user->status,
        ])->assertRedirect("/admin/users/{$user->id}");

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name_th' => 'ชื่อใหม่',
        ]);
    }
}
