<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Summarizer\SummarizeController;
use App\Http\Controllers\Proofreader\ProofreaderController;
use App\Http\Controllers\QuizmeController;
use App\Http\Controllers\StepTutorController;
use App\Http\Controllers\FiveQuestion\FiveQuestionsController;
use App\Http\Controllers\EmailWriter\EmailWriterController;
use App\Http\Controllers\ThankYouNote\ThankYouNoteController;
use App\Http\Controllers\RealWorld\RealWorldController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\TextLeveler\LevelerController;
use App\Http\Controllers\InformationalTexts\InformationalController;
use App\Http\Controllers\QOTDController;
use App\Http\Controllers\TongueTwistController;
use App\Http\Controllers\TeacherJokesController;
use App\Http\Controllers\CoachSportsPracController;
use App\Http\Controllers\BookSuggestionController;
use App\Http\Controllers\IdeaGenerator\IdeaGeneratorController;
use App\Http\Controllers\ContentCreator\ContentCreatorController;
use App\Http\Controllers\SentenceStarters\SentenceStarterController;
use App\Http\Controllers\Translator\TranslatorController;
use App\Http\Controllers\StudyHabits\StudyHabitsController;
use App\Http\Controllers\ChatWithDocs\ChatWithDocsController;
use App\Http\Controllers\EmailResponder\ResponderController;
use App\Http\Controllers\TextRewriter\RewriterController;
use App\Http\Controllers\TextScaffolder\ScaffolderController;
use App\Http\Controllers\Explanations\ExplanationsController;
use App\Http\Controllers\AssignmentScaffolder\AssignmentScaffolderController;
use App\Http\Controllers\MathReview\MathReviewController;
use App\Http\Controllers\MakeItRelevant\MakeItRelevantController;
use App\Http\Controllers\ChatconversationController;
use App\Http\Controllers\SocialStory\SocialStoryController;
use App\Http\Controllers\CharacterChat\CharacterChatController;
use App\Http\Controllers\RAGController;
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

Route::get('/tools', function () {
    return view('tool');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('virtual_tutor', function () {
    return view('virtualtutor');
});

Route::get('/virtual_tutor_chat/{book_id}', function ($book_id) {
    return view('virtualtutorchat', ['book_id' => $book_id]);
});

Route::post('/upload-endpoint', [RAGController::class, 'uploadToFastAPI']);

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // Conversation history for tutor agent
    Route::get('/tutor/conversation/history', [\App\Http\Controllers\ConversationController::class, 'history']);
    Route::post('/tutor/conversation/store', [\App\Http\Controllers\ConversationController::class, 'store']);
    // Add your protected routes 
    Route::get('/api/user-sessions', [ChatconversationController::class, 'getUserSessions'])->name('api.user_sessions');
    Route::get('/chat/history/{session_id}', [ChatconversationController::class, 'showForm']);
    Route::post('/chat', [ChatconversationController::class, 'sendMessage']);
    Route::get('/chat/api/history/{session_id}', [ChatconversationController::class, 'getHistory']);
    Route::delete('/api/sessions/{session_id}', [ChatconversationController::class, 'deleteSession'])->name('api.session.delete');
});

// ✅ Summarizer Tool
Route::get('/summarize', [SummarizeController::class, 'index']);
Route::post('/summarize', [SummarizeController::class, 'summarize']);

// thank you note
Route::get('/thankyou-note', [ThankYouNoteController::class, 'showForm'])->name('thankyou.show');
Route::post('/thankyou-note', [ThankYouNoteController::class, 'generate'])->name('thankyou.generate');

// ✅ Scaffolder Tool
Route::get('/scaffolder', 'App\Http\Controllers\ScaffolderController@showForm');
Route::post('/scaffolder', 'App\Http\Controllers\ScaffolderController@processForm');

//content creator
Route::get('/contentcreator', [ContentCreatorController::class, 'showForm'])->name('contentcreator.form');
Route::post('/contentcreator', [ContentCreatorController::class, 'generate'])->name('contentcreator.generate');

//Idea Generator
Route::get('/idea-generator', [IdeaGeneratorController::class, 'showForm'])->name('idea.show');
Route::post('/idea-generator', [IdeaGeneratorController::class, 'generate'])->name('idea.generate');

// ✅ Tutor Tool
Route::get('/tutor', 'App\Http\Controllers\TutorController@showForm');
Route::post('/tutor', 'App\Http\Controllers\TutorController@processForm');
Route::post('/tutor/clear', [App\Http\Controllers\TutorController::class, 'clearHistory'])->middleware('auth');

// ✅ Leveler Tool
Route::get('/leveler', [LevelerController::class, 'showForm'])->name('leveler.form');
Route::post('/leveler', [LevelerController::class, 'processForm'])->name('leveler.process');

// ✅ Informational Tool
Route::get('/informational', [InformationalController::class, 'showForm'])->name('informational.form');
Route::post('/informational', [InformationalController::class, 'processForm'])->name('informational.process');

// ✅ Chat with Docs Tool
Route::get('/chatwithdocs', [ChatWithDocsController::class, 'showForm'])->name('chatwithdocs.form');
Route::post('/chatwithdocs', [ChatWithDocsController::class, 'processForm'])->name('chatwithdocs.process');

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

// ✅ 5 Questions Tool
Route::get('/5questions', [FiveQuestionsController::class, 'showForm'])->name('fivequestions.form');
Route::post('/5questions', [FiveQuestionsController::class, 'processForm'])->name('fivequestions.process');

// ✅ Step Tutor
Route::get('/step-tutor', [StepTutorController::class, 'showForm']);
Route::post('/step-tutor', [StepTutorController::class, 'processForm']);
Route::post('/step-tutor/clear', [App\Http\Controllers\StepTutorController::class, 'clearHistory'])->middleware('auth');

// Routes for Book Suggestion Chatbot
Route::get('/booksuggestion', [BookSuggestionController::class, 'index']);
Route::post('/suggest', [BookSuggestionController::class, 'getSuggestions']);

//email writer
Route::get('/email-writer', [EmailWriterController::class, 'show'])->name('email.writer.show');
Route::post('/email-writer', [EmailWriterController::class, 'generate'])->name('email.writer.generate');

// Real World Agent
Route::get('/realworld', [RealWorldController::class, 'showForm'])->name('realworld.form');
Route::post('/realworld', [RealWorldController::class, 'processForm'])->name('realworld.process');

// Sentence Starter Agent
Route::get('/sentencestarter', [SentenceStarterController::class, 'showForm'])->name('sentencestarter.form');
Route::post('/sentencestarter', [SentenceStarterController::class, 'processForm'])->name('sentencestarter.process');
Route::post('/sentence-starter/followup', [SentenceStarterController::class, 'followupForm'])->name('sentencestarter.followup');


// Translator Agent
Route::get('/translator', [TranslatorController::class, 'showForm'])->name('translator.form');
Route::post('/translator', [TranslatorController::class, 'processForm'])->name('translator.process');
Route::post('/translator/followup', [TranslatorController::class, 'followUp'])->name('translator.followup');
Route::get('/translator/session/{id}', [TranslatorController::class, 'showSession'])->name('translator.session');
Route::get('/translator/conversation/{message_id}', [\App\Http\Controllers\Translator\TranslatorController::class, 'showSpecificMessages'])->name('translator.specific');



// Study Habits Agent
Route::get('/studyhabits', [StudyHabitsController::class, 'showForm'])->name('studyhabits.form');
Route::post('/studyhabits', [StudyHabitsController::class, 'processForm'])->name('studyhabits.process');

// ✅ Responder Tool
Route::get('/responder', [ResponderController::class, 'showForm'])->name('responder.form');
Route::post('/responder', [ResponderController::class, 'processForm'])->name('responder.process');

// Rewriter Tool
Route::get('/rewriter', [RewriterController::class, 'showForm'])->name('rewriter.form');
Route::post('/rewriter', [RewriterController::class, 'processForm'])->name('rewriter.process');

// Text Scaffolder Tool
Route::get('/scaffolder', [ScaffolderController::class, 'showForm'])->name('scaffolder.form');
Route::post('/scaffolder', [ScaffolderController::class, 'processForm'])->name('scaffolder.process');

// ✅ Explanations Tool
Route::get('/explanations', [ExplanationsController::class, 'showForm'])->name('explanations.form');
Route::post('/explanations', [ExplanationsController::class, 'processForm'])->name('explanations.process');

// ✅ Scaffolder Tool
Route::get('/assignmentscaffolder', [AssignmentScaffolderController::class, 'showForm'])->name('assignmentscaffolder.form');
Route::post('/assignmentscaffolder', [AssignmentScaffolderController::class, 'processForm'])->name('assignmentscaffolder.process');

// ✅ Math Review Too
Route::get('/mathreview', [MathReviewController::class, 'showForm'])->name('mathreview.form');
Route::post('/mathreview', [MathReviewController::class, 'processForm'])->name('mathreview.process');

Route::get('/makeitrelevant', [MakeItRelevantController::class, 'showForm'])->name('makeitrelevant.form');
Route::post('/makeitrelevant', [MakeItRelevantController::class, 'processForm'])->name('makeitrelevant.process');

// Route::get('/chat/history/{session_id}', [ChatconversationController::class, 'showForm']);
// Route::post('/chat', [ChatconversationController::class, 'sendMessage']);
// Route::get('/chat/api/history/{session_id}', [ChatconversationController::class, 'getHistory']);

//Social Story Tool
Route::get('/socialstory', [SocialStoryController::class, 'showForm'])->name('socialstory.form');
Route::post('/socialstory', [SocialStoryController::class, 'generate'])->name('socialstory.generate');

// Character Chat Tool
Route::get('/characterchat', [CharacterChatController::class, 'showForm'])->name('characterchat.form');
Route::post('/characterchat', [CharacterChatController::class, 'generate'])->name('characterchat.generate');

Route::post('/books', [RAGController::class, 'addBook']);
Route::get('/books', [RAGController::class, 'getBooks']);

// 📘 Units
Route::post('/units', [RAGController::class, 'addUnit']);
Route::get('/units', [RAGController::class, 'getUnits']);

// 📘 Chapters
Route::post('/chapters', [RAGController::class, 'addChapter']);
Route::get('/chapters', [RAGController::class, 'getChapters']);

// 📘 Lessons
Route::post('/lessons', [RAGController::class, 'addLesson']);
Route::get('/lessons', [RAGController::class, 'getLessons']);

Route::get('/get-first-lesson', [RAGController::class, 'getFirstLesson']);

Route::get('/virtual-tutor-chat', [RAGController::class, 'showVirtualTutorChat']);