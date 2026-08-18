<?php

namespace Tests\Feature;

use App\Models\HasResearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'citizen_id' => sprintf('%013d', random_int(1000000000000, 9999999999999)),
        ], $attrs));
    }

    private function makeResearch(string $idCard, string $name = 'Test Research'): HasResearch
    {
        return HasResearch::create([
            'id_card'              => $idCard,
            'name'                 => $name,
            'year'                 => '2024',
            'research_type_id'     => 1,
            'research_PMU_type_id' => 0,
            'research_level_id'    => 0,
            'dateAdd'              => now(),
        ]);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/researches')->assertUnauthorized();
    }

    public function test_index_returns_only_own_items_by_id_card(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();

        $this->makeResearch($user->citizen_id, 'Mine');
        $this->makeResearch($other->citizen_id, 'Not Mine');

        Sanctum::actingAs($user);

        $this->getJson('/api/researches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mine');
    }

    public function test_store_creates_research_using_logged_in_users_id_card(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/researches', [
            'name'             => 'New Research',
            'year'             => '2024',
            'research_type_id' => 1,
        ])->assertCreated();

        $this->assertDatabaseHas('has_research', [
            'id_card' => $user->citizen_id,
            'name'    => 'New Research',
        ], 'expert');
    }

    public function test_store_validates_required_fields(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/researches', [])->assertUnprocessable();
    }

    public function test_store_validates_year_length(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/researches', [
            'name'             => 'Test',
            'year'             => '25',
            'research_type_id' => 1,
        ])->assertUnprocessable();
    }

    public function test_show_returns_own_item(): void
    {
        $user     = $this->makeUser();
        $research = $this->makeResearch($user->citizen_id, 'My Paper');

        Sanctum::actingAs($user);

        $this->getJson("/api/researches/{$research->id}")
            ->assertOk()
            ->assertJsonPath('name', 'My Paper');
    }

    public function test_show_returns_404_for_other_users_item(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        $research = $this->makeResearch($other->citizen_id);

        Sanctum::actingAs($user);

        $this->getJson("/api/researches/{$research->id}")->assertNotFound();
    }

    public function test_update_modifies_item(): void
    {
        $user     = $this->makeUser();
        $research = $this->makeResearch($user->citizen_id, 'Old Name');

        Sanctum::actingAs($user);

        $this->putJson("/api/researches/{$research->id}", [
            'name'             => 'Updated Name',
            'year'             => '2025',
            'research_type_id' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('has_research', [
            'id'   => $research->id,
            'name' => 'Updated Name',
        ], 'expert');
    }

    public function test_cannot_update_other_users_item(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        $research = $this->makeResearch($other->citizen_id);

        Sanctum::actingAs($user);

        $this->putJson("/api/researches/{$research->id}", [
            'name'             => 'Hacked',
            'year'             => '2024',
            'research_type_id' => 1,
        ])->assertNotFound();
    }

    public function test_destroy_deletes_item(): void
    {
        $user     = $this->makeUser();
        $research = $this->makeResearch($user->citizen_id);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/researches/{$research->id}")->assertOk();

        $this->assertDatabaseMissing('has_research', ['id' => $research->id], 'expert');
    }

    public function test_cannot_delete_other_users_item(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        $research = $this->makeResearch($other->citizen_id);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/researches/{$research->id}")->assertNotFound();
    }
}
