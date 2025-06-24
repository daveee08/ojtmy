<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProofreaderController; // ✅ Import your controller

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes handle the AI Proofreader UI and API integration.
|
*/

Route::get('/', function () {
    return redirect()->route('proofreader.form'); // Redirect '/' to the proofreader form
});

// ✅ Proofreader routes
Route::get('/proofreader', [ProofreaderController::class, 'showForm'])->name('proofreader.form');
Route::post('/proofreader', [ProofreaderController::class, 'processForm'])->name('proofreader.process');
