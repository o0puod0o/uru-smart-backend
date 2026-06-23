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

    private function makeResearch(int $userId, string $name = 'Test Research'): HasResearch
    {
        return HasResearch::create([
            'user_id'              => $userId,
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

    public function test_index_returns_only_own_items(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $this->makeResearch($user->id, 'Mine');
        $this->makeResearch($other->id, 'Not Mine');

        Sanctum::actingAs($user);

        $this->getJson('/api/researches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mine');
    }

    public function test_store_creates_research(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/researches', [
            'name'             => 'New Research',
            'year'             => '2024',
            'research_type_id' => 1,
        ])->assertCreated();

        $this->assertDatabaseHas('has_researches', [
            'user_id' => $user->id,
            'name'    => 'New Research',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/researches', [])->assertUnprocessable();
    }

    public function test_store_validates_year_length(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/researches', [
            'name'             => 'Test',
            'year'             => '25',
            'research_type_id' => 1,
        ])->assertUnprocessable();
    }

    public function test_show_returns_own_item(): void
    {
        $user     = User::factory()->create();
        $research = $this->makeResearch($user->id, 'My Paper');

        Sanctum::actingAs($user);

        $this->getJson("/api/researches/{$research->id}")
            ->assertOk()
            ->assertJsonPath('name', 'My Paper');
    }

    public function test_show_returns_404_for_other_users_item(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $research = $this->makeResearch($other->id);

        Sanctum::actingAs($user);

        $this->getJson("/api/researches/{$research->id}")->assertNotFound();
    }

    public function test_update_modifies_item(): void
    {
        $user     = User::factory()->create();
        $research = $this->makeResearch($user->id, 'Old Name');

        Sanctum::actingAs($user);

        $this->putJson("/api/researches/{$research->id}", [
            'name'             => 'Updated Name',
            'year'             => '2025',
            'research_type_id' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('has_researches', [
            'id'   => $research->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_cannot_update_other_users_item(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $research = $this->makeResearch($other->id);

        Sanctum::actingAs($user);

        $this->putJson("/api/researches/{$research->id}", [
            'name'             => 'Hacked',
            'year'             => '2024',
            'research_type_id' => 1,
        ])->assertNotFound();
    }

    public function test_destroy_deletes_item(): void
    {
        $user     = User::factory()->create();
        $research = $this->makeResearch($user->id);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/researches/{$research->id}")->assertOk();

        $this->assertDatabaseMissing('has_researches', ['id' => $research->id]);
    }

    public function test_cannot_delete_other_users_item(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $research = $this->makeResearch($other->id);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/researches/{$research->id}")->assertNotFound();
    }
}
