<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPassResult extends Model
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    protected $fillable = ['case_id', 'pass_type', 'raw_output', 'model_identifier'];
    protected function casts(): array { return ['raw_output' => 'array']; }
    public function caseRecord(): BelongsTo { return $this->belongsTo(CaseRecord::class, 'case_id'); }
}
