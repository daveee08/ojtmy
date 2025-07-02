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
use App\Http\Controllers\RealWorldController;
use App\Http\Controllers\EmailResponderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScaffolderController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\LevelerController;
use App\Http\Controllers\InformationalController;
use App\Http\Controllers\ExplanationsController;
use App\Http\Controllers\QOTDController;
use App\Http\Controllers\TongueTwistController;
use App\Http\Controllers\TeacherJokesController;
use App\Http\Controllers\CoachSportsPracController;
use App\Http\Controllers\BookSuggestionController;
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

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/tools', function () {
    return view('tool');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // Conversation history for tutor agent
    Route::get('/tutor/conversation/history', [\App\Http\Controllers\ConversationController::class, 'history']);
    Route::post('/tutor/conversation/store', [\App\Http\Controllers\ConversationController::class, 'store']);
    // Add your protected routes here
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
Route::post('/tutor/clear', [App\Http\Controllers\TutorController::class, 'clearHistory'])->middleware('auth');

// ✅ Leveler Tool
Route::get('/leveler', 'App\Http\Controllers\LevelerController@showForm');
Route::post('/leveler', 'App\Http\Controllers\LevelerController@processForm');

// ✅ Informational Tool
Route::get('/informational', 'App\Http\Controllers\InformationalController@showForm');
Route::post('/informational', 'App\Http\Controllers\InformationalController@processForm');

// ✅ Proofreader Tool
Route::get('/proofreader', [ProofreaderController::class, 'showForm'])->name('proofreader.form');
Route::post('/proofreader', [ProofreaderController::class, 'processForm'])->name('proofreader.process');

// ✅ QuizMe Tool
Route::get('/quizme', 'App\Http\Controllers\QuizmeController@showForm');
Route::post('/quizme', 'App\Http\Controllers\QuizmeController@processForm');
Route::post('/quizme/download', 'App\Http\Controllers\QuizmeController@downloadContent')->name('quizme.download');
Route::post('/quizme/evaluate-answer', 'App\Http\Controllers\QuizmeController@evaluateAnswer');
Route::post('/quizme/chat', 'App\Http\Controllers\QuizmeController@chat');

// ✅ Qoutes of the Day
Route::get('/qotd', [QOTDController::class, 'showForm']);
Route::post('/qotd', [QOTDController::class, 'generateQuote']);
Route::post('/qotd/download', [QOTDController::class, 'downloadQuote'])->name('qotd.download');

// ✅ Tongue Twister
Route::get('/tonguetwister', [TongueTwistController::class, 'showForm']);
Route::post('/tonguetwister', [TongueTwistController::class, 'generateTongueTwister']);

// ✅ Teacher Jokes
Route::get('/teacherjokes', [TeacherJokesController::class, 'showForm']);
Route::post('/teacherjokes', [TeacherJokesController::class, 'generateJoke']);

// ✅ Coach Sports Practice
Route::get('/coachsportprac', [CoachSportsPracController::class, 'showForm']);
Route::post('/coachsportprac', [CoachSportsPracController::class, 'generatePracticePlan']);
Route::post('/coachsportprac/download', [CoachSportsPracController::class, 'downloadPracticePlan'])->name('coachsportprac.download');

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

// Routes for Book Suggestion Chatbot
Route::get('/booksuggestion', [BookSuggestionController::class, 'index']);
Route::post('/suggest', [BookSuggestionController::class, 'getSuggestions']);

//email writer
Route::get('/email-writer', [EmailWriterController::class, 'show'])->name('email.writer.show');
Route::post('/email-writer', [EmailWriterController::class, 'generate'])->name('email.writer.generate');

// Real World Agent
Route::get('/realworld', [RealWorldController::class, 'showForm'])->name('realworld.form');
Route::post('/realworld', [RealWorldController::class, 'processForm'])->name('realworld.process');
