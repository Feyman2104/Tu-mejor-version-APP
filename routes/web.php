<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PostureController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\RoutineExerciseController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing
Route::get('/', fn () => Inertia::render('Landing'))->name('landing');

// Página offline PWA
Route::get('/offline', fn () => Inertia::render('Offline'))->name('offline');

// Auth (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Onboarding (auth, onboarding incompleto)
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// App principal (auth + onboarding completo)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ejercicios — rutas estáticas ANTES de la ruta con parámetro :slug
    Route::get('/exercises/search', [ExerciseController::class, 'apiSearch'])->name('exercises.search');
    Route::get('/exercises/gifs',   [ExerciseController::class, 'gifsByKeys'])->name('exercises.gifs');
    Route::get('/exercises', [ExerciseController::class, 'index'])->name('exercises.index');
    Route::get('/exercises/{exercise:slug}', [ExerciseController::class, 'show'])->name('exercises.show');

    // Entrenamiento
    Route::get('/workout/today', [WorkoutController::class, 'today'])->name('workout.today');
    Route::get('/workout/log', [WorkoutController::class, 'log'])->name('workout.log');
    Route::post('/workout/logs', [WorkoutController::class, 'storeLog'])->name('workout.logs.store');
    Route::post('/workout/sets', [WorkoutController::class, 'storeSet'])->name('workout.sets.store');
    Route::patch('/workout/logs/{workoutLog}/complete', [WorkoutController::class, 'complete'])->name('workout.logs.complete');
    Route::get('/workout/logs/{workoutLog}', [WorkoutController::class, 'show'])->name('workout.logs.show');

    // Rutinas
    Route::post('/routines/generate', [WorkoutController::class, 'generateRoutine'])->name('routines.generate');
    Route::patch('/routine-exercises/reorder', [RoutineExerciseController::class, 'reorder'])->name('routine.exercises.reorder');

    // Postura
    Route::get('/posture', [PostureController::class, 'index'])->name('posture.index');
    Route::post('/posture/sessions', [PostureController::class, 'storeSession'])->name('posture.sessions.store');

    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');

    // Progreso
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::post('/progress', [ProgressController::class, 'store'])->name('progress.store');
});
