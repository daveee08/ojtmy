<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SummarizeController;
use App\Http\Controllers\ProofreaderController;
use App\Http\Controllers\QuizmeController;
use App\Http\Controllers\RewriterController;
use App\Http\Controllers\StepTutorController;
use App\Http\Controllers\FiveQuestionsController;
use App\Http\Controllers\EmailWriterController;
use App\Http\Controllers\ThankYouNoteController;
use App\Http\Controllers\IdeaGeneratorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Define routes for your AI tools here.
|--------------------------------------------------------------------------
*/

// Landing Page
Route::get('/', function () {
    return view('home');
});

// ✅ Summarizer Tool
Route::get('/summarize', [SummarizeController::class, 'index']);
Route::post('/summarize', [SummarizeController::class, 'summarize']);

Route::get('/thankyou-note', [ThankYouNoteController::class, 'showForm'])->name('thankyou.show');
Route::post('/thankyou-note', [ThankYouNoteController::class, 'generate'])->name('thankyou.generate');

// ✅ Scaffolder Tool
Route::get('/scaffolder', 'App\Http\Controllers\ScaffolderController@showForm');
Route::post('/scaffolder', 'App\Http\Controllers\ScaffolderController@processForm');

//Idea Generator
Route::get('/idea-generator', [IdeaGeneratorController::class, 'showForm'])->name('idea.show');
Route::post('/idea-generator', [IdeaGeneratorController::class, 'generate'])->name('idea.generate');

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

// QuizMe Tool
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

// ✅ Explanations Tool
Route::get('/explanations', 'App\Http\Controllers\ExplanationsController@showForm');
Route::post('/explanations', 'App\Http\Controllers\ExplanationsController@processForm');

// Rewriter Tool
Route::get('/rewriter', [RewriterController::class, 'showForm']);
Route::post('/rewriter', [RewriterController::class, 'processForm']);
Route::get('/rewriter', 'App\Http\Controllers\RewriterController@showForm');
Route::post('/rewriter', 'App\Http\Controllers\RewriterController@processForm');

//email writer
Route::get('/email-writer', [EmailWriterController::class, 'show'])->name('email.writer.show');
Route::post('/email-writer', [EmailWriterController::class, 'generate'])->name('email.writer.generate');
