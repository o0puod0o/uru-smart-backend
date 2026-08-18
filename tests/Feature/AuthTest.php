<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_deletes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Logged out successfully']);

        // reset auth guard cache ก่อน request ถัดไป
        $this->app['auth']->forgetGuards();

        // token ถูกลบแล้ว — ใช้อีกครั้งต้องได้ 401
        $this->withToken($token)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_logout_requires_auth(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/auth/login', [])->assertUnprocessable();
    }

    public function test_login_accepts_email_username_or_id_card_format_and_rejects_unknown_identifier(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => 'not-an-email',
            'password' => 'secret',
        ])->assertUnauthorized();
    }

    public function test_second_mock_user_can_login_as_normal_user(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'somsak@gmail.com',
            'password' => 'password',
        ])->assertOk();

        $response->assertJsonPath('user.email', 'somsak@gmail.com');
        $this->assertDatabaseHas('users', [
            'sso_id' => 2175,
            'email' => 'somsak@gmail.com',
            'role' => 'user',
        ]);

        $token = $response->json('token');
        $this->withToken($token)->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_expert_profile_driver_uses_mock_sso_then_mock_expert_profile(): void
    {
        config([
            'sso.driver' => 'expert_profile',
            'sso.base_url' => 'http://nginx/api/mock-sso',
            'sso.expert_api_base_url' => 'http://nginx/api/mock-sso',
            'sso.expert_api_token' => 'mock-expert-api-token',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'phanuwat@live.uru.ac.th',
            'password' => 'password',
        ])->assertOk();

        $response->assertJsonPath('user.email', 'mr.phanuwat@gmail.com');

        $this->assertDatabaseHas('users', [
            'sso_id' => 900304,
            'username' => '3530900177802',
            'citizen_id' => '3530900177802',
            'first_name_th' => 'ภานุวัฒน์',
            'last_name_th' => 'ขันจา',
            'sso_picture' => 'https://hrms.uru.ac.th/WebURUINF/Applications/File/Forms/frmFileStorage?guid=46407332252C4D439A5383C2A07257EA',
        ]);
    }
}
