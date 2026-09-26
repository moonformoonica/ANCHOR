<?php

namespace Tests\Feature;

use App\Models\LegalCorpusEntry;
use App\Models\ReferralInstitution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekOneFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_week_one_seed_data_stays_unverified_until_reviewed(): void
    {
        $this->assertSame('sqlite', config('database.default'));

        $this->seed();

        $this->assertDatabaseHas('legal_corpus_entries', [
            'law_name' => 'UU PDP No. 27/2022',
            'pasal_reference' => 'Pasal 65 ayat (2)',
            'status' => 'under_review',
            'last_verified_date' => null,
        ]);
        $this->assertDatabaseMissing('legal_corpus_entries', [
            'law_name' => 'UU ITE',
            'pasal_reference' => 'Pasal 27 ayat (3)',
            'status' => 'active',
        ]);
        $this->assertSame(4, ReferralInstitution::count());
        $this->assertSame(0, ReferralInstitution::where('status', 'active')->count());
        $this->assertNull(LegalCorpusEntry::first()->metadata['severity_weight']);
    }
}