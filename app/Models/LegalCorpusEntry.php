<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LegalCorpusEntry extends Model
{
    protected $fillable = ['law_name', 'pasal_reference', 'status', 'metadata'];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function cases(): BelongsToMany { return $this->belongsToMany(CaseRecord::class, 'classification_citations', 'legal_corpus_entry_id', 'case_id'); }
}
