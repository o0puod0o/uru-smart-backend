<?php

namespace Tests\Feature;

use App\Models\Education;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EducationTest extends TestCase
{
    use RefreshDatabase;

    private function makeEducation(int $userId): Education
    {
        return Education::create([
            'user_id'    => $userId,
            'degree'     => 1,
            'course'     => 'Computer Science',
            'university' => 'MIT',
            'year'       => '2020',
            'dateAdd'    => now(),
        ]);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/educations')->assertUnauthorized();
    }

    public function test_store_creates_education(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/educations', [
            'degree'     => 1,
            'course'     => 'วิทยาการคอมพิวเตอร์',
            'university' => 'มหาวิทยาลัยเทคโนโลยี',
            'year'       => '2022',
        ])->assertCreated();

        $this->assertDatabaseHas('educations', [
            'user_id' => $user->id,
            'course'  => 'วิทยาการคอมพิวเตอร์',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/educations', [])->assertUnprocessable();
    }

    public function test_show_returns_404_for_other_users_education(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $edu   = $this->makeEducation($other->id);

        Sanctum::actingAs($user);

        $this->getJson("/api/educations/{$edu->id}")->assertNotFound();
    }

    public function test_update_education(): void
    {
        $user = User::factory()->create();
        $edu  = $this->makeEducation($user->id);

        Sanctum::actingAs($user);

        $this->putJson("/api/educations/{$edu->id}", [
            'degree'     => 2,
            'course'     => 'Updated Course',
            'university' => 'Updated Uni',
            'year'       => '2023',
        ])->assertOk();

        $this->assertDatabaseHas('educations', [
            'id'     => $edu->id,
            'course' => 'Updated Course',
        ]);
    }

    public function test_destroy_education(): void
    {
        $user = User::factory()->create();
        $edu  = $this->makeEducation($user->id);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/educations/{$edu->id}")->assertOk();

        $this->assertDatabaseMissing('educations', ['id' => $edu->id]);
    }
}
