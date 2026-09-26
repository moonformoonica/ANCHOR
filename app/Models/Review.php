<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['case_id', 'reviewer_id', 'action', 'rejection_route', 'edited_output', 'notes'];

    protected function casts(): array
    {
        return ['edited_output' => 'array'];
    }

    public function caseRecord(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
