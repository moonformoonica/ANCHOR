<?php

namespace Tests\Feature;

use App\Models\AiPassResult;
use App\Models\AuditLog;
use App\Models\CaseRecord;
use App\Models\EvidenceItem;
use App\Models\Review;
use App\Models\SeverityScore;
use App\Models\User;
use App\Services\ReviewStateMachine;
use App\Services\ReviewTriageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplianceCriticalPathsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['victim', 'legal_reviewer', 'admin'] as $role) {
            Role::create(['name' => $role]);
        }
    }

    public function test_victim_case_status_and_evidence_reject_missing_invalid_and_expired_bearer_tokens(): void
    {
        $case = $this->case('AC-TOKEN', 'valid-token', now()->addDay());
        $evidence = ['evidence' => [['type' => 'link', 'url_or_file_ref' => 'https://example.test/evidence']]];

        foreach (['', 'invalid-token'] as $token) {
            $request = $this->getJson('/api/v1/cases/'.$case->public_case_id);
            if ($token !== '') {
                $request = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/cases/'.$case->public_case_id);
            }
            $request->assertUnauthorized();

            $request = $this->postJson('/api/v1/cases/'.$case->public_case_id.'/evidence', $evidence);
            if ($token !== '') {
                $request = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/cases/'.$case->public_case_id.'/evidence', $evidence);
            }
            $request->assertUnauthorized();
        }

        $expired = $this->case('AC-EXPIRED', 'expired-token', now()->subSecond());
        $this->withHeader('Authorization', 'Bearer expired-token')->getJson('/api/v1/cases/'.$expired->public_case_id)->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer expired-token')->postJson('/api/v1/cases/'.$expired->public_case_id.'/evidence', $evidence)->assertUnauthorized();
    }

    public function test_case_creation_read_and_evidence_write_are_audited_with_correct_actor_action_and_case(): void
    {
        $created = $this->postJson('/api/v1/cases', ['narrative' => 'Collector exposed my data.'])->assertCreated()->json();
        $case = CaseRecord::query()->where('public_case_id', $created['public_case_id'])->firstOrFail();

        $this->assertDatabaseHas('audit_log', [
            'actor_type' => 'victim', 'actor_id' => null, 'action' => 'POST victim.cases.store', 'case_id' => $case->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$created['secret_token'])->getJson('/api/v1/cases/'.$case->public_case_id)->assertOk();
        $this->assertDatabaseHas('audit_log', [
            'actor_type' => 'victim', 'actor_id' => null, 'action' => 'GET victim.cases.show', 'case_id' => $case->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$created['secret_token'])->postJson('/api/v1/cases/'.$case->public_case_id.'/evidence', [
            'evidence' => [['type' => 'link', 'url_or_file_ref' => 'https://example.test/evidence']],
        ])->assertCreated();
        $this->assertDatabaseHas('audit_log', [
            'actor_type' => 'victim', 'actor_id' => null, 'action' => 'POST victim.cases.evidence', 'case_id' => $case->id,
        ]);
    }

    public function test_legal_corpus_create_and_update_audit_actor_values_and_change_reason(): void
    {
        $admin = $this->userWithRole('admin');

        $created = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/corpus', [
            'law_name' => 'UU PDP No. 27/2022',
            'pasal_reference' => 'Pasal 65 ayat (2)',
            'status' => 'under_review',
            'metadata' => ['severity_weight' => null],
            'change_reason' => 'Add baseline candidate pending legal partner review.',
        ])->assertCreated()->json('data');

        $createAudit = AuditLog::query()->where('action', 'POST corpus.store')->latest('id')->firstOrFail();
        $this->assertSame('admin', $createAudit->actor_type);
        $this->assertSame($admin->id, $createAudit->actor_id);
        $this->assertSame('legal_corpus_entry', $createAudit->details['resource_type']);
        $this->assertSame($created['id'], $createAudit->details['resource_id']);
        $this->assertNull($createAudit->details['changes']['before']);
        $this->assertSame('under_review', $createAudit->details['changes']['after']['status']);
        $this->assertSame('Add baseline candidate pending legal partner review.', $createAudit->details['change_reason']);

        $this->actingAs($admin, 'sanctum')->patchJson('/api/v1/corpus/'.$created['id'], [
            'status' => 'superseded',
            'change_reason' => 'Replace with a verified and current corpus reference.',
        ])->assertOk();

        $updateAudit = AuditLog::query()->where('action', 'PATCH corpus.update')->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $updateAudit->actor_id);
        $this->assertSame($created['id'], $updateAudit->details['resource_id']);
        $this->assertSame('under_review', $updateAudit->details['changes']['before']['status']);
        $this->assertSame('superseded', $updateAudit->details['changes']['after']['status']);
        $this->assertSame('Replace with a verified and current corpus reference.', $updateAudit->details['change_reason']);
    }

    public function test_rejection_routes_to_reclassification_or_manual_handling_only(): void
    {
        $reviewer = $this->userWithRole('legal_reviewer');
        $stateMachine = app(ReviewStateMachine::class);

        $reclassify = $this->case('AC-RECLASSIFY', 'token-one', now()->addDay());
        $stateMachine->apply($reclassify, $reviewer, ['action' => 'reject', 'rejection_route' => 'reclassify_pending']);
        $this->assertDatabaseHas('cases', ['id' => $reclassify->id, 'status' => 'reclassify_pending']);
        $this->assertDatabaseHas('reviews', ['case_id' => $reclassify->id, 'action' => 'reject', 'rejection_route' => 'reclassify_pending']);

        $manual = $this->case('AC-MANUAL', 'token-two', now()->addDay());
        $stateMachine->apply($manual, $reviewer, ['action' => 'reject', 'rejection_route' => 'manual_handling']);
        $this->assertDatabaseHas('cases', ['id' => $manual->id, 'status' => 'manual_handling']);
        $this->assertDatabaseHas('reviews', ['case_id' => $manual->id, 'action' => 'reject', 'rejection_route' => 'manual_handling']);
    }

    public function test_victim_and_reviewer_roles_cannot_access_each_others_or_admin_only_endpoints(): void
    {
        $case = $this->case('AC-ROLES', 'role-token', now()->addDay());
        $victim = $this->userWithRole('victim');
        $reviewer = $this->userWithRole('legal_reviewer');
        $admin = $this->userWithRole('admin');

        $this->actingAs($victim, 'sanctum')->getJson('/api/v1/review-queue')->assertForbidden();
        $this->actingAs($victim, 'sanctum')->getJson('/api/v1/cases/'.$case->id)->assertForbidden();
        $this->actingAs($victim, 'sanctum')->getJson('/api/v1/audit-log')->assertForbidden();
        $this->actingAs($victim, 'sanctum')->getJson('/api/v1/corpus')->assertForbidden();
        $this->actingAs($victim, 'sanctum')->getJson('/api/v1/users')->assertForbidden();

        $this->actingAs($reviewer, 'sanctum')->getJson('/api/v1/review-queue')->assertOk();
        $this->actingAs($reviewer, 'sanctum')->getJson('/api/v1/audit-log')->assertForbidden();
        $this->actingAs($reviewer, 'sanctum')->getJson('/api/v1/corpus')->assertForbidden();
        $this->actingAs($reviewer, 'sanctum')->getJson('/api/v1/users')->assertForbidden();

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/review-queue')->assertOk();
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/audit-log')->assertOk();
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/corpus')->assertOk();
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users')->assertOk();
    }

    public function test_triage_historical_similarity_and_pre_fill_never_create_review_or_change_case_state(): void
    {
        $reviewer = $this->userWithRole('legal_reviewer');
        $historical = $this->case('AC-HISTORY', 'history-token', now()->addDay(), 'approved');
        $current = $this->case('AC-CURRENT', 'current-token', now()->addDay());
        foreach ([$historical, $current] as $case) {
            AiPassResult::create(['case_id' => $case->id, 'pass_type' => 'legal_classification', 'raw_output' => ['classification' => 'same'], 'model_identifier' => 'test']);
            AiPassResult::create(['case_id' => $case->id, 'pass_type' => 'hostility_score', 'raw_output' => ['score' => 80], 'model_identifier' => 'test']);
            EvidenceItem::create(['case_id' => $case->id, 'type' => 'link', 'url_or_file_ref' => 'https://example.test/'.$case->id, 'submitted_at' => now()]);
            SeverityScore::create(['case_id' => $case->id, 'breadth_score' => 20, 'intensity_score' => 80, 'computed_severity' => 'high', 'sync_flag' => false, 'computed_at' => now()]);
        }
        Review::create(['case_id' => $historical->id, 'reviewer_id' => $reviewer->id, 'action' => 'approve']);
        $reviewsBefore = Review::count();

        $triage = app(ReviewTriageService::class)->assess($current->fresh());

        $this->assertSame('historical_pattern_match', $triage['flag']);
        $this->assertSame('approve', $triage['suggested_approval']['action']);
        $this->assertTrue($triage['suggested_approval']['requires_explicit_reviewer_confirmation']);
        $this->assertSame($reviewsBefore, Review::count());
        $this->assertDatabaseHas('cases', [
            'id' => $current->id, 'status' => 'pending_review',
            'review_recommendation_confidence' => null, 'review_recommendation_source_case_id' => null,
        ]);
    }

    private function case(string $publicCaseId, string $token, $expiresAt, string $status = 'pending_review'): CaseRecord
    {
        return CaseRecord::create([
            'public_case_id' => $publicCaseId,
            'token_hash' => Hash::make($token),
            'token_expires_at' => $expiresAt,
            'status' => $status,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
