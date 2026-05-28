<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\NutritionController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PostureController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\RoutineController;
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

    // Password reset
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// Social login (no guest middleware — Socialite handles redirect after OAuth)
Route::get('/auth/google',          [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);
Route::get('/auth/facebook',          [SocialiteController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [SocialiteController::class, 'handleFacebookCallback']);

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
    Route::get('/exercises/muscle-groups', [ExerciseController::class, 'muscleGroups'])->name('exercises.muscle-groups');
    Route::get('/exercises/gifs',   [ExerciseController::class, 'gifsByKeys'])->name('exercises.gifs');
    Route::get('/exercises', [ExerciseController::class, 'index'])->name('exercises.index');
    Route::get('/exercises/{exercise:slug}', [ExerciseController::class, 'show'])->name('exercises.show');

    // Entrenamiento
    Route::get('/workout', [WorkoutController::class, 'index'])->name('workout.index');
    Route::get('/workout/today', [WorkoutController::class, 'today'])->name('workout.today');
    Route::get('/workout/empty', [WorkoutController::class, 'empty'])->name('workout.empty');
    Route::get('/workout/session/{routineDay}', [WorkoutController::class, 'session'])->name('workout.session');
    Route::get('/workout/log', [WorkoutController::class, 'log'])->name('workout.log');
    Route::post('/workout/logs', [WorkoutController::class, 'storeLog'])->name('workout.logs.store');
    Route::post('/workout/sets', [WorkoutController::class, 'storeSet'])->name('workout.sets.store');
    Route::patch('/workout/logs/{workoutLog}/complete', [WorkoutController::class, 'complete'])->name('workout.logs.complete');
    Route::delete('/workout/logs/{workoutLog}', [WorkoutController::class, 'destroy'])->name('workout.logs.destroy');
    Route::get('/workout/logs/{workoutLog}', [WorkoutController::class, 'show'])->name('workout.logs.show');

    // Rutinas
    Route::post('/routines/generate', [WorkoutController::class, 'generateRoutine'])->name('routines.generate');
    Route::get('/routines/create', [RoutineController::class, 'create'])->name('routines.create');
    Route::post('/routines', [RoutineController::class, 'store'])->name('routines.store');
    Route::get('/routines/{routine}/edit', [RoutineController::class, 'edit'])->name('routines.edit');
    Route::patch('/routines/{routine}', [RoutineController::class, 'update'])->name('routines.update');
    Route::delete('/routines/{routine}', [RoutineController::class, 'destroy'])->name('routines.destroy');
    Route::patch('/routine-exercises/reorder', [RoutineExerciseController::class, 'reorder'])->name('routine.exercises.reorder');
    Route::patch('/routine-exercises/{routineExercise}/deactivate', [RoutineExerciseController::class, 'deactivate'])->name('routine.exercises.deactivate');

    // Postura
    Route::get('/posture', [PostureController::class, 'index'])->name('posture.index');
    Route::post('/posture/sessions', [PostureController::class, 'storeSession'])->name('posture.sessions.store');

    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');

    // Nutrición
    Route::get('/nutrition', [NutritionController::class, 'index'])->name('nutrition.index');
    Route::get('/nutrition/{dietPlan}', [NutritionController::class, 'show'])->name('nutrition.show');
    Route::post('/nutrition/regenerate', [NutritionController::class, 'regenerate'])->name('nutrition.regenerate');
    Route::post('/nutrition/meals/{meal}/items', [NutritionController::class, 'addItem'])->name('nutrition.items.store');
    Route::patch('/nutrition/meal-items/{item}', [NutritionController::class, 'updateItem'])->name('nutrition.items.update');
    Route::delete('/nutrition/meal-items/{item}', [NutritionController::class, 'destroyItem'])->name('nutrition.items.destroy');

    // Progreso
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::post('/progress', [ProgressController::class, 'store'])->name('progress.store');
});
