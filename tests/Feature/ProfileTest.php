<?php

namespace Tests\Feature;

use App\Models\Department;
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
            'prefix_th'     => 'ดร.',
            'first_name_th' => 'สมชาย',
            'last_name_th'  => 'ใจดี',
            'prefix_en'     => 'Dr.',
            'first_name_en' => 'Somchai',
            'last_name_en'  => 'Jaidee',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonFragment([
                'full_name_th' => 'ดร.สมชาย ใจดี',
                'full_name_en' => 'Dr. Somchai Jaidee',
                'email'        => $user->email,
            ]);
    }

    public function test_me_returns_editable_fields(): void
    {
        $user = User::factory()->create([
            'phone_mobile' => '0812345678',
            'bio'          => 'bio text',
            'prefix_th'    => 'ดร.',
            'birth_date'   => '1990-05-20',
            'sub_dep_id'   => null,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonFragment([
                'phone_mobile' => '0812345678',
                'bio'          => 'bio text',
                'prefix_id'    => 'ดร.',
                'birthdate'    => '1990-05-20',
                'sub_unit_id'  => null,
            ]);
    }

    public function test_me_returns_sub_unit_id_field(): void
    {
        $user = User::factory()->create(['sub_dep_id' => null]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me')->assertOk();
        $this->assertArrayHasKey('sub_unit_id', $response->json());
        $this->assertArrayHasKey('birthdate', $response->json());
        $this->assertArrayHasKey('prefix_id', $response->json());
    }

    public function test_update_profile(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/me', [
            'phone_mobile' => '0812345678',
            'bio'          => 'updated bio',
            'line_id'      => 'myline',
            'prefix_id'    => 'ดร.',
            'birthdate'    => '1985-03-15',
        ])->assertOk()->assertJsonPath('message', 'อัพเดทข้อมูลสำเร็จ');

        $this->assertDatabaseHas('users', [
            'id'           => $user->id,
            'phone_mobile' => '0812345678',
            'bio'          => 'updated bio',
            'prefix_th'    => 'ดร.',
        ]);
        // birth_date ตรวจจาก response (SQLite/MySQL format ต่างกัน)
        $fresh = $user->fresh();
        $this->assertSame('1985-03-15', $fresh->birth_date->format('Y-m-d'));
    }

    public function test_update_prefix_id_maps_to_prefix_th(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/me', ['prefix_id' => 'ผศ.'])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'prefix_th' => 'ผศ.']);
    }

    public function test_update_birthdate_maps_to_birth_date(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/me', ['birthdate' => '1990-01-01'])->assertOk();

        $this->assertSame('1990-01-01', $user->fresh()->birth_date->format('Y-m-d'));
    }

    public function test_update_sub_unit_id_maps_to_sub_dep_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // สร้าง sub_department ก่อน
        \Illuminate\Support\Facades\DB::table('sub_departments')->insert([
            'sub_dep_id' => 99, 'dep_id' => 1, 'name' => 'ทดสอบ',
        ]);

        $this->putJson('/api/me', ['sub_unit_id' => 99])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'sub_dep_id' => 99]);
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

    public function test_update_with_main_unit_updates_department(): void
    {
        $user = User::factory()->create();
        Department::insert(['dep_id' => 10, 'name' => 'วิทยาศาสตร์', 'name_en' => 'Science']);

        Sanctum::actingAs($user);

        $this->putJson('/api/me', ['main_unit' => 10])->assertOk();

        $this->assertDatabaseHas('users', [
            'id'                 => $user->id,
            'department_id'      => 10,
            'department_name_th' => 'วิทยาศาสตร์',
        ]);
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
