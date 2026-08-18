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

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'citizen_id' => sprintf('%013d', random_int(1000000000000, 9999999999999)),
        ], $attrs));
    }

    private function makeEducation(string $idCard): Education
    {
        return Education::create([
            'id_card'    => $idCard,
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

    public function test_store_creates_education_using_logged_in_users_id_card(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/educations', [
            'degree'     => 1,
            'course'     => 'Computer Science',
            'university' => 'Tech University',
            'year'       => '2022',
        ])->assertCreated();

        $this->assertDatabaseHas('education', [
            'id_card' => $user->citizen_id,
            'course'  => 'Computer Science',
        ], 'expert');
    }

    public function test_store_validates_required_fields(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/educations', [])->assertUnprocessable();
    }

    public function test_show_returns_404_for_other_users_education(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        $edu   = $this->makeEducation($other->citizen_id);

        Sanctum::actingAs($user);

        $this->getJson("/api/educations/{$edu->id}")->assertNotFound();
    }

    public function test_update_education(): void
    {
        $user = $this->makeUser();
        $edu  = $this->makeEducation($user->citizen_id);

        Sanctum::actingAs($user);

        $this->putJson("/api/educations/{$edu->id}", [
            'degree'     => 2,
            'course'     => 'Updated Course',
            'university' => 'Updated Uni',
            'year'       => '2023',
        ])->assertOk();

        $this->assertDatabaseHas('education', [
            'id'     => $edu->id,
            'course' => 'Updated Course',
        ], 'expert');
    }

    public function test_destroy_education(): void
    {
        $user = $this->makeUser();
        $edu  = $this->makeEducation($user->citizen_id);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/educations/{$edu->id}")->assertOk();

        $this->assertDatabaseMissing('education', ['id' => $edu->id], 'expert');
    }
}
