<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_prefixes_returns_hardcoded_list(): void
    {
        $this->getJson('/api/prefixes')
            ->assertOk()
            ->assertJsonFragment(['id' => 'นาย', 'name' => 'นาย'])
            ->assertJsonFragment(['id' => 'ดร.', 'name' => 'ดร.']);
    }

    public function test_lines_returns_hardcoded_list(): void
    {
        $this->getJson('/api/lines')
            ->assertOk()
            ->assertJsonFragment(['id' => 'สายวิชาการ'])
            ->assertJsonFragment(['id' => 'สายบริหาร']);
    }

    public function test_positions_returns_array(): void
    {
        $this->getJson('/api/positions')->assertOk()->assertJsonIsArray();
    }

    public function test_main_units_returns_array(): void
    {
        $this->getJson('/api/main-units')->assertOk()->assertJsonIsArray();
    }

    public function test_sub_units_returns_array(): void
    {
        $this->getJson('/api/sub-units')->assertOk()->assertJsonIsArray();
    }

    public function test_ref_profile_options_has_expected_keys(): void
    {
        $this->getJson('/api/ref/profile-options')
            ->assertOk()
            ->assertJsonStructure(['prefixes', 'positions', 'lines', 'main_units', 'sub_units']);
    }

    public function test_ref_search_options_has_expected_keys(): void
    {
        $this->getJson('/api/ref/search-options')
            ->assertOk()
            ->assertJsonStructure(['search_from', 'expertise_groups', 'interests']);
    }

    public function test_ref_research_types_returns_array(): void
    {
        $this->getJson('/api/ref/research-types')->assertOk()->assertJsonIsArray();
    }

    public function test_ref_journal_types_returns_array(): void
    {
        $this->getJson('/api/ref/journal-types')->assertOk()->assertJsonIsArray();
    }

    public function test_ref_degrees_returns_array(): void
    {
        $this->getJson('/api/ref/degrees')->assertOk()->assertJsonIsArray();
    }

    public function test_requires_auth(): void
    {
        // logout
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/prefixes')->assertUnauthorized();
    }
}
