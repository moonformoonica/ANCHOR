<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseNarrative extends Model
{
    public $timestamps = false;

    protected $fillable = ['case_id', 'narrative_text', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function caseRecord(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_id');
    }
}
