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

    public function test_update_profile_persists_mobile_profile_fields_and_unit_aliases(): void
    {
        $department = \App\Models\Department::query()->create([
            'dep_id' => 99,
            'name' => 'Main unit 99',
        ]);
        \App\Models\SubDepartment::query()->create([
            'sub_dep_id' => 88,
            'dep_id' => $department->dep_id,
            'name' => 'Sub unit 88',
        ]);

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
            'sub_unit'     => 88,
        ])->assertOk()->assertJsonPath('message', 'อัพเดทข้อมูลสำเร็จ');

        $this->assertDatabaseHas('users', [
            'id'           => $user->id,
            'phone_mobile' => '0812345678',
            'bio'          => 'updated bio',
            'line_id'      => 'myline',
            'prefix_th'    => 'Dr.',
            'department_id' => 99,
            'sub_dep_id'   => 88,
        ]);

        $this->assertSame('1985-03-15', $user->fresh()->birth_date->format('Y-m-d'));

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.main_unit', 99)
            ->assertJsonPath('data.sub_unit', 88)
            ->assertJsonPath('data.sub_unit_id', 88);
    }

    public function test_update_profile_accepts_info_module_unit_ids_without_cross_database_validation(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/me', [
            'main_unit' => 4,
            'sub_unit' => 62,
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'department_id' => 4,
            'sub_dep_id' => 62,
        ]);
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
