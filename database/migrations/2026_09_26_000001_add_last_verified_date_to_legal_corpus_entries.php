<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_corpus_entries', function (Blueprint $table) {
            $table->date('last_verified_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('legal_corpus_entries', function (Blueprint $table) {
            $table->dropColumn('last_verified_date');
        });
    }
};