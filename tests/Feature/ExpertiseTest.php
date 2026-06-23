<?php

namespace Tests\Feature;

use App\Models\HasExpert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpertiseTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/expertises')->assertUnauthorized();
    }

    public function test_index_returns_own_expertise(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $expert = HasExpert::create(['name' => 'Machine Learning', 'date_add' => now()]);

        DB::table('users_expert')->insert(['user_id' => $user->id,  'expert_id' => $expert->expert_id]);
        DB::table('users_expert')->insert(['user_id' => $other->id, 'expert_id' => $expert->expert_id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/expertises')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_store_by_expert_id(): void
    {
        $user   = User::factory()->create();
        $expert = HasExpert::create(['name' => 'AI', 'date_add' => now()]);

        Sanctum::actingAs($user);

        $this->postJson('/api/expertises', ['expert_id' => $expert->expert_id])
            ->assertCreated();

        $this->assertDatabaseHas('users_expert', [
            'user_id'   => $user->id,
            'expert_id' => $expert->expert_id,
        ]);
    }

    public function test_store_by_new_name_creates_expert(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/expertises', ['name' => 'Quantum Computing'])
            ->assertCreated();

        $this->assertDatabaseHas('has_experts', ['name' => 'Quantum Computing']);
        $this->assertDatabaseHas('users_expert', ['user_id' => $user->id]);
    }

    public function test_store_requires_expert_id_or_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/expertises', [])->assertStatus(422);
    }

    public function test_no_duplicate_expertise(): void
    {
        $user   = User::factory()->create();
        $expert = HasExpert::create(['name' => 'PHP', 'date_add' => now()]);

        DB::table('users_expert')->insert(['user_id' => $user->id, 'expert_id' => $expert->expert_id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/expertises', ['expert_id' => $expert->expert_id])
            ->assertCreated();

        $this->assertDatabaseCount('users_expert', 1);
    }

    public function test_destroy_removes_expertise(): void
    {
        $user   = User::factory()->create();
        $expert = HasExpert::create(['name' => 'Laravel', 'date_add' => now()]);

        $pivotId = DB::table('users_expert')->insertGetId([
            'user_id'   => $user->id,
            'expert_id' => $expert->expert_id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/expertises/{$pivotId}")->assertOk();

        $this->assertDatabaseMissing('users_expert', ['id' => $pivotId]);
    }

    public function test_cannot_delete_other_users_expertise(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $expert  = HasExpert::create(['name' => 'Java', 'date_add' => now()]);
        $pivotId = DB::table('users_expert')->insertGetId([
            'user_id'   => $other->id,
            'expert_id' => $expert->expert_id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/expertises/{$pivotId}")->assertNotFound();
    }
}
