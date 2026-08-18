<?php

namespace Tests\Feature;

use App\Models\{HasJournal,Proposal,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_index_returns_only_logged_in_users_expert_journals(): void
    {
        $viewer = User::factory()->create(['citizen_id' => '1111111111111']);
        $owner = User::factory()->create(['citizen_id' => '2222222222222']);
        HasJournal::create(['id_card'=>$viewer->citizen_id,'name'=>'Mine','year'=>'2026','journal_type_id'=>1,'dateAdd'=>now()]);
        HasJournal::create(['id_card'=>$owner->citizen_id,'name'=>'Other','year'=>'2026','journal_type_id'=>1,'dateAdd'=>now()]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/journals')->assertOk()
            ->assertJsonPath('data.0.name','Mine')
            ->assertJsonCount(1, 'data');
    }

    public function test_journal_write_uses_id_card_from_token_and_blocks_non_owner(): void
    {
        $owner=User::factory()->create(['citizen_id' => '3333333333333']);
        $other=User::factory()->create(['citizen_id' => '4444444444444']);

        Sanctum::actingAs($owner);
        $journalId = $this->postJson('/api/journals', [
            'name' => 'Mine',
            'year' => '2026',
            'journal_type_id' => 1,
        ])->assertCreated()->json('id');

        $this->assertDatabaseHas('has_journal', [
            'id' => $journalId,
            'id_card' => $owner->citizen_id,
        ], 'expert');

        Sanctum::actingAs($other);
        $this->getJson("/api/journals/{$journalId}")->assertNotFound();
    }

    public function test_owner_is_taken_from_token_and_only_admin_approves_proposal(): void
    {
        $owner=User::factory()->create(); $other=User::factory()->create();
        Sanctum::actingAs($owner);
        $proposalId=$this->postJson('/api/proposals',['owner_user_id'=>$other->id,'title'=>'P1','year'=>'2026','budget'=>100])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('proposals',['id'=>$proposalId,'owner_user_id'=>$owner->id]);
        $this->putJson("/api/proposals/{$proposalId}",['status'=>'approved'])->assertForbidden();

        $admin=User::factory()->create(['role'=>'admin']); Sanctum::actingAs($admin);
        $this->putJson("/api/proposals/{$proposalId}",['status'=>'approved'])->assertOk();
        $this->assertDatabaseHas('notifications',['user_id'=>$owner->id,'type'=>'proposal_status']);
    }

    public function test_file_access_is_limited_to_owner_or_admin(): void
    {
        Storage::fake('private');
        $owner=User::factory()->create(); $other=User::factory()->create();
        $proposal=Proposal::create(['owner_user_id'=>$owner->id,'title'=>'Files','year'=>'2026','status'=>'draft']);
        Sanctum::actingAs($owner);
        $id=$this->post('/api/files',['entity_type'=>'proposal','entity_id'=>$proposal->id,'file'=>UploadedFile::fake()->create('report.pdf',20,'application/pdf')],['Accept'=>'application/json'])->assertCreated()->json('id');
        Sanctum::actingAs($other);
        $this->get("/api/files/{$id}/download",['Accept'=>'application/json'])->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['role'=>'admin']));
        $this->deleteJson("/api/files/{$id}")->assertOk();
    }

    public function test_admin_routes_reject_normal_users(): void
    {
        Sanctum::actingAs(User::factory()->create(['role'=>'user']));
        $this->getJson('/api/admin/users')->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['role'=>'admin']));
        $this->getJson('/api/admin/users')->assertOk()->assertJsonStructure(['data','meta']);
    }
}
