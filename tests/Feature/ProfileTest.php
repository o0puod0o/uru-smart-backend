<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_auth(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_returns_user_data(): void
    {
        $user = User::factory()->create([
            'prefix_th'     => 'Dr.',
            'first_name_th' => 'Somchai',
            'last_name_th'  => 'Jaidee',
            'prefix_en'     => 'Dr.',
            'first_name_en' => 'Somchai',
            'last_name_en'  => 'Jaidee',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonFragment([
                'full_name_th' => 'Dr.Somchai Jaidee',
                'full_name_en' => 'Dr. Somchai Jaidee',
                'email'        => $user->email,
            ]);
    }

    public function test_me_returns_mobile_editable_and_master_display_fields(): void
    {
        $user = User::factory()->create([
            'phone_mobile' => '0812345678',
            'bio'          => 'bio text',
            'prefix_th'    => 'Dr.',
            'birth_date'   => '1990-05-20',
            'sub_dep_id'   => null,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonFragment([
                'phone_mobile' => '0812345678',
                'bio'          => 'bio text',
                'prefix_id'    => 'Dr.',
                'birthdate'    => '1990-05-20',
                'sub_unit_id'  => null,
            ]);
    }

    public function test_me_has_master_display_keys(): void
    {
        $user = User::factory()->create(['sub_dep_id' => null]);

        Sanctum::actingAs($user);

        $profile = $this->getJson('/api/me')->assertOk()->json('data');

        $this->assertArrayHasKey('sub_unit_id', $profile);
        $this->assertArrayHasKey('birthdate', $profile);
        $this->assertArrayHasKey('prefix_id', $profile);
    }

    public function test_update_profile_updates_only_mobile_owned_fields(): void
    {
        $user = User::factory()->create([
            'prefix_th' => 'Mr.',
            'birth_date' => '1980-01-01',
            'department_id' => 10,
            'sub_dep_id' => 20,
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/me', [
            'phone_mobile' => '0812345678',
            'bio'          => 'updated bio',
            'line_id'      => 'myline',
            'prefix_id'    => 'Dr.',
            'birthdate'    => '1985-03-15',
            'main_unit'    => 99,
            'sub_unit_id'  => 88,
        ])->assertOk()->assertJsonPath('message', 'อัพเดทข้อมูลสำเร็จ');

        $this->assertDatabaseHas('users', [
            'id'           => $user->id,
            'phone_mobile' => '0812345678',
            'bio'          => 'updated bio',
            'line_id'      => 'myline',
            'prefix_th'    => 'Mr.',
            'department_id' => 10,
            'sub_dep_id'   => 20,
        ]);

        $this->assertSame('1980-01-01', $user->fresh()->birth_date->format('Y-m-d'));
    }

    public function test_update_requires_auth(): void
    {
        $this->putJson('/api/me', ['phone_mobile' => '0812345678'])->assertUnauthorized();
    }

    public function test_update_validates_website_url(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/me', ['website' => 'not-a-valid-url'])
            ->assertUnprocessable();
    }

    public function test_push_token_saved(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/push-token', ['push_token' => 'ExponentPushToken[abc123]'])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id'         => $user->id,
            'push_token' => 'ExponentPushToken[abc123]',
        ]);
    }

    public function test_push_token_requires_token_field(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/push-token', [])->assertUnprocessable();
    }
}
