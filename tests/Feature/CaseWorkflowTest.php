<?php

namespace Tests\Feature;

use App\Models\CaseRecord;
use App\Models\LegalCorpusEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'legal_reviewer']);
        Role::create(['name' => 'admin']);
        LegalCorpusEntry::create(['law_name' => 'UU ITE', 'pasal_reference' => 'Pasal 27 ayat (3)', 'status' => 'active']);
    }

    public function test_victim_token_is_bearer_only_and_final_output_requires_approval(): void
    {
        $created = $this->postJson('/api/v1/cases', ['narrative' => 'A debt collector posted my personal data.'])->assertCreated()->json();
        $this->getJson('/api/v1/cases/'.$created['public_case_id'])->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer '.$created['secret_token'])->getJson('/api/v1/cases/'.$created['public_case_id'])
            ->assertOk()->assertJsonPath('status', 'pending_review')->assertJsonMissingPath('final_output');
    }

    public function test_rejection_requires_a_route_and_reclassification_returns_to_review(): void
    {
        $case = CaseRecord::create(['public_case_id' => 'AC-TEST', 'token_hash' => bcrypt('token'), 'status' => 'pending_review']);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('legal_reviewer');
        $this->actingAs($reviewer, 'sanctum')->postJson('/api/v1/cases/'.$case->id.'/review', ['action' => 'reject'])->assertUnprocessable();
        $this->actingAs($reviewer, 'sanctum')->postJson('/api/v1/cases/'.$case->id.'/review', ['action' => 'reject', 'rejection_route' => 'manual_handling'])->assertOk();
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'status' => 'manual_handling']);
    }
}
