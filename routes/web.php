<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SummarizeController;
use App\Http\Controllers\ProofreaderController;
use App\Http\Controllers\QuizmeController;
use App\Http\Controllers\RewriterController;
use App\Http\Controllers\StepTutorController;
use App\Http\Controllers\FiveQuestionsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Define routes for your AI tools here.
|--------------------------------------------------------------------------
*/

// ✅ Landing Page
Route::get('/', function () {
    return view('tool');
});

// ✅ Summarizer Tool
Route::get('/summarize', [SummarizeController::class, 'index']);
Route::post('/summarize', [SummarizeController::class, 'summarize']);

// ✅ Scaffolder Tool
Route::get('/scaffolder', 'App\Http\Controllers\ScaffolderController@showForm');
Route::post('/scaffolder', 'App\Http\Controllers\ScaffolderController@processForm');

// ✅ Tutor Tool
Route::get('/tutor', 'App\Http\Controllers\TutorController@showForm');
Route::post('/tutor', 'App\Http\Controllers\TutorController@processForm');

// ✅ Leveler Tool
Route::get('/leveler', 'App\Http\Controllers\LevelerController@showForm');
Route::post('/leveler', 'App\Http\Controllers\LevelerController@processForm');

// ✅ Informational Tool
Route::get('/informational', 'App\Http\Controllers\InformationalController@showForm');
Route::post('/informational', 'App\Http\Controllers\InformationalController@processForm');

// ✅ Rewriter Tool
Route::get('/rewriter', 'App\Http\Controllers\RewriterController@showForm');
Route::post('/rewriter', 'App\Http\Controllers\RewriterController@processForm');

// ✅ Proofreader Tool
Route::get('/proofreader', [ProofreaderController::class, 'showForm'])->name('proofreader.form');
Route::post('/proofreader', [ProofreaderController::class, 'processForm'])->name('proofreader.process');

// ✅ QuizMe Tool
Route::get('/quizme', [QuizmeController::class, 'showForm']);
Route::post('/quizme', [QuizmeController::class, 'processForm']);
Route::post('/quizme/download', [QuizmeController::class, 'downloadContent'])->name('quizme.download');
Route::post('/quizme/evaluate-answer', [QuizmeController::class, 'evaluateAnswer']);
Route::post('/quizme/chat', [QuizmeController::class, 'chat']);

// ✅ 5 Questions Agent
Route::get('/5questions', [FiveQuestionsController::class, 'showForm'])->name('fivequestions.form');
Route::post('/5questions', [FiveQuestionsController::class, 'processForm'])->name('fivequestions.process');

// ✅ Step Tutor
Route::get('/step-tutor', [StepTutorController::class, 'showForm']);
Route::post('/step-tutor', [StepTutorController::class, 'processForm']);
