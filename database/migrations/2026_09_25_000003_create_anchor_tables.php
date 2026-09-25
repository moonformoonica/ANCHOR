<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('public_case_id')->unique();
            $table->string('token_hash');
            $table->string('status')->index();
            $table->decimal('review_recommendation_confidence', 5, 4)->nullable()->index();
            $table->foreignId('review_recommendation_source_case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('case_narratives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('cases')->cascadeOnDelete();
            $table->longText('narrative_text');
            $table->timestamp('submitted_at');
        });
        Schema::create('evidence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('type');
            $table->text('url_or_file_ref');
            $table->timestamp('timeline_at')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
        Schema::create('ai_pass_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('pass_type');
            $table->jsonb('raw_output');
            $table->string('model_identifier');
            $table->timestamps();
            $table->index(['case_id', 'pass_type']);
        });
        Schema::create('legal_corpus_entries', function (Blueprint $table) {
            $table->id();
            $table->string('law_name');
            $table->string('pasal_reference');
            $table->string('status')->default('active')->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->unique(['law_name', 'pasal_reference']);
        });
        Schema::create('classification_citations', function (Blueprint $table) {
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('legal_corpus_entry_id')->constrained()->restrictOnDelete();
            $table->primary(['case_id', 'legal_corpus_entry_id']);
        });
        Schema::create('severity_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('cases')->cascadeOnDelete();
            $table->unsignedTinyInteger('breadth_score');
            $table->unsignedTinyInteger('intensity_score');
            $table->string('computed_severity');
            $table->boolean('sync_flag')->default(false)->index();
            $table->timestamp('computed_at');
            $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->string('rejection_route')->nullable();
            $table->jsonb('edited_output')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['case_id', 'action']);
        });
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('action');
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->jsonb('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('severity_scores');
        Schema::dropIfExists('classification_citations');
        Schema::dropIfExists('legal_corpus_entries');
        Schema::dropIfExists('ai_pass_results');
        Schema::dropIfExists('evidence_items');
        Schema::dropIfExists('case_narratives');
        Schema::dropIfExists('cases');
    }
};
