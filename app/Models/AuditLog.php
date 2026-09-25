<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $table = 'audit_log';
    protected $fillable = ['actor_type', 'actor_id', 'action', 'case_id', 'details', 'created_at'];
    protected function casts(): array { return ['details' => 'array', 'created_at' => 'datetime']; }
    public function caseRecord(): BelongsTo { return $this->belongsTo(CaseRecord::class, 'case_id'); }
}
