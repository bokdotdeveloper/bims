<?php

use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\AssistanceRecordController;
use App\Http\Controllers\BeneficiaryGroupController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Report exports
    Route::get('reports/beneficiaries/pdf',   [ReportController::class, 'beneficiariesPdf'])->name('reports.beneficiaries.pdf');
    Route::get('reports/beneficiaries/excel', [ReportController::class, 'beneficiariesExcel'])->name('reports.beneficiaries.excel');
    Route::get('reports/projects/pdf',        [ReportController::class, 'projectsPdf'])->name('reports.projects.pdf');
    Route::get('reports/projects/excel',      [ReportController::class, 'projectsExcel'])->name('reports.projects.excel');
    Route::get('reports/trainings/pdf',       [ReportController::class, 'trainingsPdf'])->name('reports.trainings.pdf');
    Route::get('reports/trainings/excel',     [ReportController::class, 'trainingsExcel'])->name('reports.trainings.excel');
    Route::get('reports/assistance/pdf',      [ReportController::class, 'assistancePdf'])->name('reports.assistance.pdf');
    Route::get('reports/assistance/excel',    [ReportController::class, 'assistanceExcel'])->name('reports.assistance.excel');

    Route::resource('beneficiaries', BeneficiaryController::class)->except(['create', 'edit']);
    Route::get('beneficiaries/{beneficiary}/details', [BeneficiaryController::class, 'details'])->name('beneficiaries.details');

    // Family members
    Route::get('beneficiaries/{beneficiary}/family', [FamilyMemberController::class, 'index'])->name('beneficiaries.family.index');
    Route::post('beneficiaries/{beneficiary}/family', [FamilyMemberController::class, 'store'])->name('beneficiaries.family.store');
    Route::put('beneficiaries/{beneficiary}/family/{member}', [FamilyMemberController::class, 'update'])->name('beneficiaries.family.update');
    Route::delete('beneficiaries/{beneficiary}/family/{member}', [FamilyMemberController::class, 'destroy'])->name('beneficiaries.family.destroy');
    Route::get('beneficiaries-search', [FamilyMemberController::class, 'searchBeneficiaries'])->name('beneficiaries.search');
    Route::get('beneficiaries-similarity', [FamilyMemberController::class, 'checkSimilarity'])->name('beneficiaries.similarity');
Route::get('beneficiaries-family-match', [FamilyMemberController::class, 'checkFamilyMemberMatch'])->name('beneficiaries.family-match');

    Route::resource('projects', ProjectController::class)->except(['create', 'edit']);
    Route::resource('trainings', TrainingController::class)->except(['create', 'edit']);
    Route::resource('assistance-records', AssistanceRecordController::class)->except(['create', 'edit']);
    Route::resource('beneficiary-groups', BeneficiaryGroupController::class)->except(['create', 'edit', 'show']);
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    // Project beneficiary management
    Route::get('projects/{project}/beneficiaries', [ProjectController::class, 'listBeneficiaries'])->name('projects.beneficiaries.index');
    Route::get('projects/{project}/available-beneficiaries', [ProjectController::class, 'availableBeneficiaries'])->name('projects.beneficiaries.available');
    Route::post('projects/{project}/beneficiaries', [ProjectController::class, 'enrollBeneficiary'])->name('projects.beneficiaries.store');
    Route::delete('projects/{project}/beneficiaries/{beneficiary}', [ProjectController::class, 'removeBeneficiary'])->name('projects.beneficiaries.destroy');

    // Training participant management
    Route::get('trainings/{training}/participants', [TrainingController::class, 'listParticipants'])->name('trainings.participants.index');
    Route::get('trainings/{training}/available-participants', [TrainingController::class, 'availableParticipants'])->name('trainings.participants.available');
    Route::post('trainings/{training}/participants', [TrainingController::class, 'addParticipant'])->name('trainings.participants.store');
    Route::delete('trainings/{training}/participants/{beneficiary}', [TrainingController::class, 'removeParticipant'])->name('trainings.participants.destroy');

    // Beneficiary group member management
    Route::get('beneficiary-groups/{group}/members', [BeneficiaryGroupController::class, 'listMembers'])->name('beneficiary-groups.members.index');
    Route::get('beneficiary-groups/{group}/available-members', [BeneficiaryGroupController::class, 'availableMembers'])->name('beneficiary-groups.members.available');
    Route::post('beneficiary-groups/{group}/members', [BeneficiaryGroupController::class, 'addMember'])->name('beneficiary-groups.members.store');
    Route::delete('beneficiary-groups/{group}/members/{beneficiary}', [BeneficiaryGroupController::class, 'removeMember'])->name('beneficiary-groups.members.destroy');

    // Notifications
    Route::get('app-notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('app-notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('app-notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('app-notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});
