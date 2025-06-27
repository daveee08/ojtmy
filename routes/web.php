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
    return view('welcome');
});

//Tools Hub
Route::view('/tools-hub', 'hub');

//Summarizer Tool
Route::get('/summarize', [SummarizeController::class, 'index']);
Route::post('/summarize', [SummarizeController::class, 'summarize']);

//Thank you note
Route::get('/thankyounote', 'App\Http\Controllers\ThankYouNoteController@showForm')->name('thankyou.show');
Route::post('/thankyounote', 'App\Http\Controllers\ThankYouNoteController@processForm')->name('thankyou.generate');

//Idea Generator
Route::get('/idea-generator', [IdeaGeneratorController::class, 'showForm'])->name('idea.show');
Route::post('/idea-generator', [IdeaGeneratorController::class, 'generate'])->name('idea.generate');

Route::get('/', function(){
    return view('home');
});

//Tutor
Route::get('/tutor', 'App\Http\Controllers\TutorController@showForm');
Route::post('/tutor', 'App\Http\Controllers\TutorController@processForm');

//Leveler
Route::get('/leveler', 'App\Http\Controllers\LevelerController@showForm');
Route::post('/leveler', 'App\Http\Controllers\LevelerController@processForm');

//Informational
Route::get('/informational', 'App\Http\Controllers\InformationalController@showForm');
Route::post('/informational', 'App\Http\Controllers\InformationalController@processForm');

//Proofreader
Route::get('/proofreader', [ProofreaderController::class, 'showForm'])->name('proofreader.form');
Route::post('/proofreader', [ProofreaderController::class, 'processForm'])->name('proofreader.process');

// QuizMe Tool
Route::get('/quizme', [QuizmeController::class, 'showForm']);
Route::post('/quizme', [QuizmeController::class, 'processForm']);
Route::post('/quizme/download', [QuizmeController::class, 'downloadContent'])->name('quizme.download');
Route::post('/quizme/evaluate-answer', [QuizmeController::class, 'evaluateAnswer']);
Route::post('/quizme/chat', [QuizmeController::class, 'chat']);

// Rewriter Tool
Route::get('/rewriter', [RewriterController::class, 'showForm']);
Route::post('/rewriter', [RewriterController::class, 'processForm']);
Route::get('/rewriter', 'App\Http\Controllers\RewriterController@showForm');
Route::post('/rewriter', 'App\Http\Controllers\RewriterController@processForm');

// 5 Questions Agent
Route::get('/5questions', [FiveQuestionsController::class, 'showForm'])->name('fivequestions.form');
Route::post('/5questions', [FiveQuestionsController::class, 'processForm'])->name('fivequestions.process');

//step by step
Route::get('/step-tutor', [StepTutorController::class, 'showForm']);
Route::post('/step-tutor', [StepTutorController::class, 'processForm']);

//email writer
Route::get('/email-writer', [EmailWriterController::class, 'show'])->name('email.writer.show');
Route::post('/email-writer', [EmailWriterController::class, 'generate'])->name('email.writer.generate');
