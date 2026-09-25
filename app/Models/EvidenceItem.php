<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceItem extends Model
{
    protected $fillable = ['case_id', 'type', 'url_or_file_ref', 'timeline_at', 'submitted_at'];
    protected function casts(): array { return ['timeline_at' => 'datetime', 'submitted_at' => 'datetime']; }
    public function caseRecord(): BelongsTo { return $this->belongsTo(CaseRecord::class, 'case_id'); }
}
