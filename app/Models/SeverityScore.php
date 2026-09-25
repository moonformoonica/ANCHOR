<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeverityScore extends Model
{
    protected $fillable = ['case_id', 'breadth_score', 'intensity_score', 'computed_severity', 'sync_flag', 'computed_at'];
    protected function casts(): array { return ['sync_flag' => 'boolean', 'computed_at' => 'datetime']; }
    public function caseRecord(): BelongsTo { return $this->belongsTo(CaseRecord::class, 'case_id'); }
}
