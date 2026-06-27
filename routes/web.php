<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\PraLeadController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Sales\RequestMasukController;
use App\Http\Controllers\Sales\LeadController;
use App\Http\Controllers\Sales\DesignRequestController as SalesDesignRequestController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\ProjectController;
use App\Http\Controllers\Drafter\DesignRequestController as DrafterDesignRequestController;
use App\Http\Controllers\Shared\ActivityController;
use App\Http\Controllers\Shared\CalendarController;
use App\Http\Controllers\Shared\ReportController;
use App\Http\Controllers\Shared\DocumentController;
use Illuminate\Support\Facades\Route;

// ---------- Guest ----------
Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// ---------- Authenticated ----------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ---------- Shared (sales + admin) ----------
    Route::middleware('role:sales,sales_admin')->group(function () {
        Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
        Route::get('/activities/create', [ActivityController::class, 'create'])->name('activities.create');
        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::put('/activities/{activity}/status', [ActivityController::class, 'updateStatus'])->name('activities.status');

        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // Calendar & Reports juga untuk drafter
    Route::middleware('role:drafter')->group(function () {
        Route::get('/drafter/calendar', [CalendarController::class, 'index'])->name('drafter.calendar.index');
        Route::get('/drafter/reports', [ReportController::class, 'index'])->name('drafter.reports.index');
    });

    // Documents (semua role)
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // ---------- Sales Admin ----------
    Route::middleware('role:administrator,sales_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pra-leads', [PraLeadController::class, 'index'])->name('pra-leads.index');
        Route::post('/pra-leads', [PraLeadController::class, 'store'])->name('pra-leads.store');
        Route::put('/pra-leads/{praLead}', [PraLeadController::class, 'update'])->name('pra-leads.update');
        Route::delete('/pra-leads/{praLead}', [PraLeadController::class, 'destroy'])->name('pra-leads.destroy');

        Route::get('/assignment', [AssignmentController::class, 'index'])->name('assignment.index');
        Route::post('/assignment/reassign', [AssignmentController::class, 'reassign'])->name('assignment.reassign');

        // Manage User (CRUD + atur akses/role)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // ---------- Sales ----------
    Route::middleware('role:sales')->prefix('sales')->name('sales.')->group(function () {
        Route::get('/request-masuk', [RequestMasukController::class, 'index'])->name('request-masuk.index');
        Route::post('/request-masuk/{praLead}/accept', [RequestMasukController::class, 'accept'])->name('request-masuk.accept');
        Route::post('/request-masuk/{praLead}/reject', [RequestMasukController::class, 'reject'])->name('request-masuk.reject');

        Route::resource('leads', LeadController::class);

        Route::get('/design-requests', [SalesDesignRequestController::class, 'index'])->name('design-requests.index');
        Route::get('/design-requests/create', [SalesDesignRequestController::class, 'create'])->name('design-requests.create');
        Route::post('/design-requests', [SalesDesignRequestController::class, 'store'])->name('design-requests.store');
        Route::get('/design-requests/{designRequest}', [SalesDesignRequestController::class, 'show'])->name('design-requests.show');

        Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
        Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
        Route::post('/quotations/{quotation}/won', [QuotationController::class, 'markWon'])->name('quotations.won');
        Route::post('/quotations/{quotation}/lost', [QuotationController::class, 'markLost'])->name('quotations.lost');

        Route::resource('projects', ProjectController::class)->only(['index', 'create', 'store', 'show']);
    });

    // Customers (sales + admin)
    Route::middleware('role:sales,sales_admin')->prefix('sales')->name('sales.')->group(function () {
        Route::resource('customers', CustomerController::class)->except(['destroy']);
    });

    // ---------- Drafter ----------
    Route::middleware('role:drafter')->prefix('drafter')->name('drafter.')->group(function () {
        Route::get('/design-requests', [DrafterDesignRequestController::class, 'index'])->name('design-requests.index');
        Route::get('/design-requests/{designRequest}', [DrafterDesignRequestController::class, 'show'])->name('design-requests.show');
        Route::put('/design-requests/{designRequest}/progress', [DrafterDesignRequestController::class, 'updateProgress'])->name('design-requests.progress');
        Route::post('/design-requests/{designRequest}/feedback', [DrafterDesignRequestController::class, 'submitFeedback'])->name('design-requests.feedback');
    });
});
