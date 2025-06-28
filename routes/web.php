<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SummarizeController;
use App\Http\Controllers\ProofreaderController;
use App\Http\Controllers\QuizmeController;
use App\Http\Controllers\RewriterController;
use App\Http\Controllers\FiveQuestionsController;
use App\Http\Controllers\StepTutorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Define routes for your AI tools here.
|--------------------------------------------------------------------------
*/

// ✅ Landing Page
Route::get('/', function () {
    return view('home');
});

// // ✅ Tools Hub
// Route::view('/tools-hub', 'hub');

// ✅ Summarizer Tool
Route::get('/summarize', [SummarizeController::class, 'index']);
Route::post('/summarize', [SummarizeController::class, 'summarize']);

// ✅ Tutor Tool
Route::get('/tutor', 'App\Http\Controllers\TutorController@showForm');
Route::post('/tutor', 'App\Http\Controllers\TutorController@processForm');

// ✅ Leveler Tool
Route::get('/leveler', 'App\Http\Controllers\LevelerController@showForm');
Route::post('/leveler', 'App\Http\Controllers\LevelerController@processForm');

// ✅ Informational Tool
Route::get('/informational', 'App\Http\Controllers\InformationalController@showForm');
Route::post('/informational', 'App\Http\Controllers\InformationalController@processForm');

// ✅ Proofreader Tool
Route::get('/proofreader', [ProofreaderController::class, 'showForm'])->name('proofreader.form');
Route::post('/proofreader', [ProofreaderController::class, 'processForm'])->name('proofreader.process');

// ✅ Rewriter Tool
Route::get('/rewriter', [RewriterController::class, 'showForm']);
Route::post('/rewriter', [RewriterController::class, 'processForm']);

// ✅ QuizMe Tool
Route::get('/quizme', [QuizmeController::class, 'showForm']);
Route::post('/quizme', [QuizmeController::class, 'processForm']);
Route::post('/quizme/download', [QuizmeController::class, 'downloadContent'])->name('quizme.download');
Route::post('/quizme/evaluate-answer', [QuizmeController::class, 'evaluateAnswer']);
Route::post('/quizme/chat', [QuizmeController::class, 'chat']);

// ✅ 5 Questions Tool
Route::get('/5questions', [FiveQuestionsController::class, 'showForm'])->name('fivequestions.form');
Route::post('/5questions', [FiveQuestionsController::class, 'processForm'])->name('fivequestions.process');

// ✅ Step Tutor Tool
Route::get('/step-tutor', [StepTutorController::class, 'showForm']);
Route::post('/step-tutor', [StepTutorController::class, 'processForm']);
