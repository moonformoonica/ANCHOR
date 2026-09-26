<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->index();
            $table->text('contact_channel')->nullable();
            $table->string('region_scope')->nullable();
            $table->string('status')->default('needs_verification')->index();
            $table->date('last_verified_date')->nullable();
            $table->text('rationale')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['name', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_institutions');
    }
};