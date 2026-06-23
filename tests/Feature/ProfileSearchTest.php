<?php

namespace Tests\Feature;

use App\Models\HasExpert;
use App\Models\HasInterest;
use App\Models\HasJournal;
use App\Models\HasProceeding;
use App\Models\HasResearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs($this->makeUser());
    }

    public function test_requires_auth(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/profile-search')->assertUnauthorized();
    }

    public function test_returns_all_users_when_no_filter(): void
    {
        $this->makeUser(['first_name_th' => 'อาจารย์หนึ่ง']);
        $this->makeUser(['first_name_th' => 'อาจารย์สอง']);

        $this->getJson('/api/profile-search')
            ->assertOk()
            ->assertJsonIsArray();
    }

    public function test_search_by_first_name(): void
    {
        $this->makeUser(['first_name_th' => 'สมชาย', 'last_name_th' => 'ใจดี']);
        $this->makeUser(['first_name_th' => 'สมหญิง', 'last_name_th' => 'รักดี']);

        $this->getJson('/api/profile-search?search_from=first_name&keyword=สมชาย')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.full_name_th', fn ($v) => str_contains($v, 'สมชาย'));
    }

    public function test_search_by_last_name(): void
    {
        $this->makeUser(['last_name_th' => 'เฉพาะคน']);
        $this->makeUser(['last_name_th' => 'อื่นๆ']);

        $this->getJson('/api/profile-search?search_from=last_name&keyword=เฉพาะคน')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_default_search_matches_name_and_email(): void
    {
        $target = $this->makeUser([
            'first_name_th' => 'เทสเตอร์',
            'last_name_th'  => 'นามสกุล',
        ]);
        $this->makeUser(['first_name_th' => 'คนอื่น', 'last_name_th' => 'คนอื่น']);

        $this->getJson('/api/profile-search?keyword=เทสเตอร์')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $target->id);
    }

    public function test_search_by_expertise_group(): void
    {
        $target = $this->makeUser();
        $other  = $this->makeUser();

        $expert = HasExpert::create(['name' => 'Machine Learning', 'date_add' => now()]);
        DB::table('users_expert')->insert(['user_id' => $target->id, 'expert_id' => $expert->expert_id]);

        HasExpert::create(['name' => 'Other Field', 'date_add' => now()]);

        $this->getJson('/api/profile-search?expertise_group=Machine+Learning')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $target->id);
    }

    public function test_search_by_interest(): void
    {
        $target = $this->makeUser();
        $this->makeUser();

        HasInterest::create(['user_id' => $target->id, 'name' => 'AI Ethics', 'dateAdd' => now()]);

        $this->getJson('/api/profile-search?interest=AI+Ethics')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $target->id);
    }

    public function test_search_by_research_keyword(): void
    {
        $target = $this->makeUser();
        $this->makeUser();

        HasResearch::create([
            'user_id'              => $target->id,
            'name'                 => 'Deep Learning Applications',
            'year'                 => '2024',
            'research_type_id'     => 1,
            'research_PMU_type_id' => 0,
            'research_level_id'    => 0,
            'dateAdd'              => now(),
        ]);

        $this->getJson('/api/profile-search?search_from=research&keyword=Deep+Learning')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $target->id);
    }

    public function test_limit_parameter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->makeUser();
        }

        $this->getJson('/api/profile-search?limit=2')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_response_has_expected_fields(): void
    {
        $this->getJson('/api/profile-search')
            ->assertOk()
            ->assertJsonStructure(['*' => [
                'id', 'full_name_th', 'full_name_en', 'email', 'picture', 'position',
            ]]);
    }

    public function test_validates_search_from_values(): void
    {
        $this->getJson('/api/profile-search?search_from=invalid_field')
            ->assertUnprocessable();
    }
}
