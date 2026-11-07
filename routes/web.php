<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Display\QueueDisplayController;
use App\Http\Controllers\Staff\QueueController;
use App\Http\Controllers\Student\TicketController;
use App\Http\Middleware\EnsurePasswordChange;
use Illuminate\Support\Facades\Route;

Route::get('/display', [QueueDisplayController::class, 'index'])->name('display.index');
Route::get('/display/{department:prefix}', [QueueDisplayController::class, 'show'])->name('display.show');

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.change.update');
});

Route::middleware(['auth', EnsurePasswordChange::class])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function (): void {
        Route::get('/dashboard', [TicketController::class, 'index'])->name('dashboard');
        Route::get('/queue-status', [TicketController::class, 'refresh'])->name('queue-status');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    });

    Route::prefix('staff')->name('staff.')->middleware('role:staff')->group(function (): void {
        Route::get('/dashboard', [QueueController::class, 'index'])->name('dashboard');
        Route::get('/queue-status', [QueueController::class, 'refresh'])->name('queue-status');
        Route::post('/call-next', [QueueController::class, 'callNext'])->name('call-next');
        Route::post('/tickets/{ticket}/complete', [QueueController::class, 'complete'])
            ->name('tickets.complete');
        Route::post('/tickets/{ticket}/skip', [QueueController::class, 'skip'])->name('tickets.skip');
        Route::post('/tickets/{ticket}/recall', [QueueController::class, 'recall'])->name('tickets.recall');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::patch('/departments/{department}', [DepartmentController::class, 'update'])
            ->name('departments.update');
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::patch('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::patch('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::post('/staff/{staff}/temporary-password', [StaffController::class, 'resetPassword'])
            ->name('staff.password.reset');
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::patch('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::post('/students/{student}/temporary-password', [StudentController::class, 'resetPassword'])
            ->name('students.password.reset');
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });
});
