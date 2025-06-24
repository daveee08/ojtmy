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
Route::get('/', function(){
    return view('welcome');
});

Route::get('/', function () {
    return redirect()->route('proofreader.form'); // Redirect '/' to the proofreader form
});

// ✅ Proofreader routes
Route::get('/proofreader', [ProofreaderController::class, 'showForm'])->name('proofreader.form');
Route::post('/proofreader', [ProofreaderController::class, 'processForm'])->name('proofreader.process');


Route::get('/quizme', 'App\Http\Controllers\QuizmeController@showForm');
Route::post('/quizme', 'App\Http\Controllers\QuizmeController@processForm');
Route::post('/quizme/download', 'App\Http\Controllers\QuizmeController@downloadContent')->name('quizme.download');
Route::post('/quizme/evaluate-answer', 'App\Http\Controllers\QuizmeController@evaluateAnswer');
Route::post('/quizme/chat', 'App\Http\Controllers\QuizmeController@chat');

Route::get('/rewriter', 'App\Http\Controllers\RewriterController@showForm');
Route::post('/rewriter', 'App\Http\Controllers\RewriterController@processForm');
