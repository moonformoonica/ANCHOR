<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CorpusController;
use App\Http\Controllers\Api\InternalAiPassResultController;
use App\Http\Controllers\Api\ReviewCaseController;
use App\Http\Controllers\Api\ReviewQueueController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\VictimCaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/token', [AuthController::class, 'token'])->name('auth.token');
    Route::post('cases', [VictimCaseController::class, 'store'])->name('victim.cases.store');
    Route::middleware('victim.case')->group(function (): void {
        Route::post('cases/{public_case_id}/evidence', [VictimCaseController::class, 'addEvidence'])->name('victim.cases.evidence');
        Route::get('cases/{public_case_id}', [VictimCaseController::class, 'show'])->name('victim.cases.show');
    });
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('review-queue', [ReviewQueueController::class, 'index'])->name('review.queue');
        Route::get('cases/{case}', [ReviewCaseController::class, 'show'])->whereNumber('case')->name('review.cases.show');
        Route::post('cases/{case}/review', [ReviewCaseController::class, 'review'])->whereNumber('case')->name('review.cases.review');
        Route::get('corpus', [CorpusController::class, 'index'])->name('corpus.index');
        Route::post('corpus', [CorpusController::class, 'store'])->name('corpus.store');
        Route::patch('corpus/{corpus}', [CorpusController::class, 'update'])->name('corpus.update');
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::middleware('internal.ai')->post('internal/cases/{case}/ai-pass-result', [InternalAiPassResultController::class, 'store'])->whereNumber('case')->name('internal.ai-pass-result');
    });
});
