<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentFeedbackController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->controller(AuthController::class)->group(function (): void {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->middleware('throttle:login')->name('login.store');
    Route::get('/register', 'showRegistration')->name('register');
    Route::post('/register', 'register')->middleware('throttle:register')->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [StudentController::class, 'show'])->whereNumber('student')->name('students.show');
    Route::post('/students/{student}/feedback', StudentFeedbackController::class)
        ->whereNumber('student')->name('students.feedback.send');
    Route::get('/interventions', [InterventionController::class, 'index'])->name('interventions.index');
    Route::post('/students/{student}/interventions', [InterventionController::class, 'store'])
        ->whereNumber('student')->name('students.interventions.store');
    Route::patch('/interventions/{intervention}/complete', [InterventionController::class, 'complete'])
        ->whereNumber('intervention')->name('interventions.complete');
    Route::patch('/interventions/{intervention}/ignore', [InterventionController::class, 'ignore'])
        ->whereNumber('intervention')->name('interventions.ignore');

    Route::get('/imports', [CsvImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/create', [CsvImportController::class, 'create'])->name('imports.create');
    Route::post('/imports', [CsvImportController::class, 'store'])->name('imports.store');
    Route::get('/imports/{csvImport}', [CsvImportController::class, 'show'])
        ->whereNumber('csvImport')->name('imports.show');
});
