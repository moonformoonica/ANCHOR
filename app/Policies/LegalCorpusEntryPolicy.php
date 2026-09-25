<?php

namespace App\Policies;

use App\Models\LegalCorpusEntry;
use App\Models\User;

class LegalCorpusEntryPolicy
{
    public function viewAny(User $user): bool { return $user->hasRole('admin'); }
    public function create(User $user): bool { return $user->hasRole('admin'); }
    public function update(User $user, LegalCorpusEntry $entry): bool { return $user->hasRole('admin'); }
}
