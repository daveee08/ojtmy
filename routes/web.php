<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SummarizeController;
use App\Http\Controllers\ProofreaderController; // ✅ Import your controller



Route::get('/', [SummarizeController::class, 'index']);
Route::post('/summarize', [SummarizeController::class, 'summarize']);
Route::view('/tools-hub', 'hub');

Route::get('/', function(){
    return view('informational');
});

Route::get('/tutor', 'App\Http\Controllers\TutorController@showForm');
Route::post('/tutor', 'App\Http\Controllers\TutorController@processForm');

Route::get('/leveler', 'App\Http\Controllers\LevelerController@showForm');
Route::post('/leveler', 'App\Http\Controllers\LevelerController@processForm');

Route::get('/proofreader', [ProofreaderController::class, 'showForm'])->name('proofreader.form');
Route::post('/proofreader', [ProofreaderController::class, 'processForm'])->name('proofreader.process');


Route::get('/quizme', 'App\Http\Controllers\QuizmeController@showForm');
Route::post('/quizme', 'App\Http\Controllers\QuizmeController@processForm');
Route::post('/quizme/download', 'App\Http\Controllers\QuizmeController@downloadContent')->name('quizme.download');
Route::post('/quizme/evaluate-answer', 'App\Http\Controllers\QuizmeController@evaluateAnswer');
Route::post('/quizme/chat', 'App\Http\Controllers\QuizmeController@chat');

Route::get('/rewriter', 'App\Http\Controllers\RewriterController@showForm');
Route::post('/rewriter', 'App\Http\Controllers\RewriterController@processForm');
