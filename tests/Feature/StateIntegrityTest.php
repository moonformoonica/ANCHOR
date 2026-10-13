<?php

namespace Tests\Feature;

use App\Models\CaseRecord;
use App\Models\Review;
use App\Models\SeverityScore;
use App\Models\User;
use App\Services\CaseProcessingService;
use App\Services\ReviewStateMachine;
use App\Services\SeverityCombinationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Guards the separation between severity calculation and workflow status.
 *
 * A severity score is derived data. It must never, on its own, move a case back
 * into the review queue and undo a reviewer's decision.
 *
 * Every call below passes a freshly loaded model on purpose. That mirrors how
 * the real controllers invoke these services through route model binding, and
 * it is what makes the defect reproducible: Eloquent skips the UPDATE statement
 * when the attribute is not dirty, so a stale in-memory instance can hide the bug.
 */
class StateIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_recompute_on_pending_review_case_does_not_cause_an_unintended_transition(): void
    {
        [$case] = $this->processedCase('AC-INTEGRITY1');

        $this->assertSame('pending_review', $case->fresh()->status);

        app(SeverityCombinationService::class)->recompute($case->fresh());

        $this->assertSame('pending_review', $case->fresh()->status);
    }

    public function test_recording_an_ai_pass_does_not_demote_an_approved_case(): void
    {
        [$case] = $this->processedCase('AC-INTEGRITY2');
        $this->approve($case);

        $this->assertSame('approved', $case->fresh()->status);

        app(CaseProcessingService::class)->record(
            $case->fresh(),
            'legal_classification',
            ['classification' => 'late_reclassification', 'cited_pasal' => [], 'draft_report' => 'Late draft.'],
            'test-model-v1'
        );

        $this->assertSame('approved', $case->fresh()->status);
        $this->assertNotNull(SeverityScore::query()->where('case_id', $case->id)->first());
    }

    public function test_internal_ai_endpoint_cannot_demote_an_approved_case(): void
    {
        [$case, $token] = $this->processedCase('AC-INTEGRITY3');
        $this->approve($case);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/cases/'.$case->public_case_id)
            ->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('final_output.classification', 'unclassified_pending_legal_review');

        $aiToken = User::factory()->create()->createToken('internal-test', ['internal:ai'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$aiToken)
            ->postJson('/api/v1/internal/cases/'.$case->id.'/ai-pass-result', [
                'pass_type' => 'hostility_score',
                'raw_output' => ['score' => 91, 'rationale' => 'Late hostile pass.'],
                'model_identifier' => 'test-model-v1',
            ])
            ->assertCreated();

        $this->assertSame('approved', $case->fresh()->status);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/cases/'.$case->public_case_id)
            ->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('final_output.classification', 'unclassified_pending_legal_review');
    }

    public function test_internal_ai_endpoint_cannot_rescue_a_case_handed_to_manual_handling(): void
    {
        [$case] = $this->processedCase('AC-INTEGRITY4');
        $reviewer = User::factory()->create();

        app(ReviewStateMachine::class)->apply($case->fresh(), $reviewer, [
            'action' => 'reject',
            'rejection_route' => 'manual_handling',
            'notes' => 'Requires out-of-band legal handling.',
        ]);

        $this->assertSame('manual_handling', $case->fresh()->status);

        $aiToken = User::factory()->create()->createToken('internal-test', ['internal:ai'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$aiToken)
            ->postJson('/api/v1/internal/cases/'.$case->id.'/ai-pass-result', [
                'pass_type' => 'legal_classification',
                'raw_output' => ['classification' => 'late_reclassification', 'cited_pasal' => [], 'draft_report' => 'Late draft.'],
                'model_identifier' => 'test-model-v1',
            ])
            ->assertCreated();

        $this->assertSame('manual_handling', $case->fresh()->status);
    }

    public function test_reclassify_pending_case_stays_reclassify_pending_when_scores_are_recomputed(): void
    {
        [$case] = $this->processedCase('AC-INTEGRITY5');
        $reviewer = User::factory()->create();

        app(ReviewStateMachine::class)->apply($case->fresh(), $reviewer, [
            'action' => 'reject',
            'rejection_route' => 'reclassify_pending',
            'notes' => 'Re-run classification with this context.',
        ]);

        $this->assertSame('reclassify_pending', $case->fresh()->status);

        app(SeverityCombinationService::class)->recompute($case->fresh());

        $this->assertSame('reclassify_pending', $case->fresh()->status);
        $this->assertDatabaseHas('reviews', [
            'case_id' => $case->id,
            'action' => 'reject',
            'rejection_route' => 'reclassify_pending',
        ]);
    }

    public function test_repeated_recompute_is_idempotent_for_scores_and_leaves_status_untouched(): void
    {
        [$case] = $this->processedCase('AC-INTEGRITY6');
        $this->approve($case);

        $combination = app(SeverityCombinationService::class);

        $first = $combination->recompute($case->fresh())->fresh();
        $second = $combination->recompute($case->fresh())->fresh();

        $this->assertSame($first->breadth_score, $second->breadth_score);
        $this->assertSame($first->intensity_score, $second->intensity_score);
        $this->assertSame($first->computed_severity, $second->computed_severity);
        $this->assertSame($first->sync_flag, $second->sync_flag);
        $this->assertSame(1, SeverityScore::query()->where('case_id', $case->id)->count());
        $this->assertSame('approved', $case->fresh()->status);
    }

    public function test_severity_tier_is_computed_by_deterministic_service_not_taken_from_ai_output(): void
    {
        [$case] = $this->processedCase('AC-INTEGRITY7');

        // The classification stub reports severity_signal = 0. Changing only that
        // AI-reported number must not move the deterministic tier boundary.
        $before = SeverityScore::query()->where('case_id', $case->id)->firstOrFail();

        app(CaseProcessingService::class)->record(
            $case->fresh(),
            'legal_classification',
            ['classification' => 'x', 'severity_signal' => 100, 'cited_pasal' => [], 'draft_report' => 'y'],
            'test-model-v1'
        );

        $after = SeverityScore::query()->where('case_id', $case->id)->firstOrFail();

        $this->assertSame($before->computed_severity, $after->computed_severity);
        $this->assertSame($before->breadth_score, $after->breadth_score);
        $this->assertSame($before->intensity_score, $after->intensity_score);
    }

    public function test_reprocess_after_victim_adds_evidence_still_requeues_the_case_for_review(): void
    {
        [$case, $token] = $this->processedCase('AC-INTEGRITY8');
        $this->approve($case);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/cases/'.$case->public_case_id.'/evidence', [
                'evidence' => [['type' => 'link', 'url_or_file_ref' => 'https://example.test/new-evidence']],
            ])
            ->assertCreated();

        $this->assertSame('pending_review', $case->fresh()->status);
    }

    public function test_state_machine_still_refuses_review_actions_outside_pending_review(): void
    {
        [$case] = $this->processedCase('AC-INTEGRITY9');
        $this->approve($case);

        $this->expectException(ValidationException::class);

        app(ReviewStateMachine::class)->apply($case->fresh(), User::factory()->create(), ['action' => 'approve']);
    }

    /**
     * @return array{0: CaseRecord, 1: string}
     */
    private function processedCase(string $publicCaseId): array
    {
        $token = 'token-'.$publicCaseId;
        $case = CaseRecord::create([
            'public_case_id' => $publicCaseId,
            'token_hash' => Hash::make($token),
            'token_expires_at' => now()->addDay(),
            'status' => 'intake',
        ]);

        app(CaseProcessingService::class)->process($case);

        return [$case, $token];
    }

    private function approve(CaseRecord $case): Review
    {
        return app(ReviewStateMachine::class)->apply($case->fresh(), User::factory()->create(), ['action' => 'approve']);
    }
}