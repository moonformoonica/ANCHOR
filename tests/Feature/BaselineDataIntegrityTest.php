<?php

namespace Tests\Feature;

use App\Models\LegalCorpusEntry;
use App\Models\ReferralInstitution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the Week 1 legal and institutional baseline.
 *
 * The baseline is research input, not settled law. Nothing here may silently
 * become active, verified, or weighted before a human approves it, and seeding
 * must stay reproducible so a paper result can be re-run.
 */
class BaselineDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_CORPUS_COUNT = 13;

    private const EXPECTED_INSTITUTION_COUNT = 4;

    public function test_baseline_corpus_has_the_expected_number_of_entries(): void
    {
        $this->seed();

        $this->assertSame(self::EXPECTED_CORPUS_COUNT, LegalCorpusEntry::count());
        $this->assertSame(self::EXPECTED_INSTITUTION_COUNT, ReferralInstitution::count());
    }

    public function test_no_baseline_corpus_entry_is_promoted_or_weighted(): void
    {
        $this->seed();

        $this->assertSame(0, LegalCorpusEntry::where('status', 'active')->count());
        $this->assertSame(self::EXPECTED_CORPUS_COUNT, LegalCorpusEntry::where('status', 'under_review')->count());
        $this->assertSame(self::EXPECTED_CORPUS_COUNT, LegalCorpusEntry::whereNull('last_verified_date')->count());

        foreach (LegalCorpusEntry::all() as $entry) {
            $this->assertNull(
                data_get($entry->metadata, 'severity_weight'),
                "severity_weight must stay null until the legal-weighting method is approved: {$entry->law_name} {$entry->pasal_reference}"
            );
            $this->assertNotNull(data_get($entry->metadata, 'review_note'), 'review_note must be retained on every baseline entry');
            $this->assertSame('week_1_v0.2', data_get($entry->metadata, 'baseline'));
        }
    }

    public function test_baseline_institutions_stay_unverified_and_have_no_contact_channel(): void
    {
        $this->seed();

        $this->assertSame(0, ReferralInstitution::where('status', 'active')->count());
        $this->assertSame(self::EXPECTED_INSTITUTION_COUNT, ReferralInstitution::where('status', 'needs_verification')->count());
        $this->assertSame(self::EXPECTED_INSTITUTION_COUNT, ReferralInstitution::whereNull('contact_channel')->count());
        $this->assertSame(self::EXPECTED_INSTITUTION_COUNT, ReferralInstitution::whereNull('last_verified_date')->count());

        foreach (ReferralInstitution::all() as $institution) {
            $this->assertNotEmpty($institution->rationale, 'routing rationale must be retained for each baseline institution');
            $this->assertNotEmpty($institution->notes);
        }
    }

    public function test_seeding_twice_does_not_duplicate_or_reactivate_baseline_rows(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(self::EXPECTED_CORPUS_COUNT, LegalCorpusEntry::count());
        $this->assertSame(self::EXPECTED_INSTITUTION_COUNT, ReferralInstitution::count());
        $this->assertSame(self::EXPECTED_CORPUS_COUNT, LegalCorpusEntry::where('status', 'under_review')->count());
        $this->assertSame(self::EXPECTED_INSTITUTION_COUNT, ReferralInstitution::where('status', 'needs_verification')->count());
    }

    public function test_seeding_is_idempotent_even_when_the_demo_admin_account_already_exists(): void
    {
        $this->seed();

        $usersAfterFirstRun = \App\Models\User::count();

        $this->seed();

        $this->assertSame($usersAfterFirstRun, \App\Models\User::count());
    }

    public function test_legacy_pre_2024_ite_numbering_is_superseded_rather_than_deleted(): void
    {
        LegalCorpusEntry::create([
            'law_name' => 'UU ITE',
            'pasal_reference' => 'Pasal 27 ayat (3)',
            'status' => 'active',
            'metadata' => ['legacy' => true],
        ]);

        $this->seed();

        $legacy = LegalCorpusEntry::query()
            ->where('law_name', 'UU ITE')
            ->where('pasal_reference', 'Pasal 27 ayat (3)')
            ->firstOrFail();

        $this->assertSame('superseded', $legacy->status);
        $this->assertTrue(data_get($legacy->metadata, 'legacy'), 'prior metadata must be preserved when superseding');
        $this->assertNotNull(data_get($legacy->metadata, 'superseded_reason'));
        $this->assertNull(data_get($legacy->metadata, 'severity_weight'));
    }
}