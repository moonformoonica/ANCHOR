<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CaseRecord extends Model
{
    use HasFactory;

    protected $table = 'cases';

    protected $fillable = [
        'public_case_id', 'token_hash', 'status', 'review_recommendation_confidence',
        'review_recommendation_source_case_id',
    ];

    protected function casts(): array
    {
        return ['review_recommendation_confidence' => 'decimal:4'];
    }

    public function narrative(): HasOne { return $this->hasOne(CaseNarrative::class, 'case_id'); }
    public function evidenceItems(): HasMany { return $this->hasMany(EvidenceItem::class, 'case_id'); }
    public function aiPassResults(): HasMany { return $this->hasMany(AiPassResult::class, 'case_id'); }
    public function severityScore(): HasOne { return $this->hasOne(SeverityScore::class, 'case_id'); }
    public function reviews(): HasMany { return $this->hasMany(Review::class, 'case_id'); }
    public function citations(): BelongsToMany { return $this->belongsToMany(LegalCorpusEntry::class, 'classification_citations', 'case_id', 'legal_corpus_entry_id'); }
    public function recommendationSource(): BelongsTo { return $this->belongsTo(self::class, 'review_recommendation_source_case_id'); }
    public function scopePendingReview(Builder $query): Builder { return $query->where('status', 'pending_review'); }
}
