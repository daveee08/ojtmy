<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', function(){
    return view('welcome');
});

Route::get('/quizme', 'App\Http\Controllers\QuizmeController@showForm');
Route::post('/quizme', 'App\Http\Controllers\QuizmeController@processForm');
Route::post('/quizme/download', 'App\Http\Controllers\QuizmeController@downloadContent')->name('quizme.download');
Route::post('/quizme/evaluate-answer', 'App\Http\Controllers\QuizmeController@evaluateAnswer');
Route::post('/quizme/chat', 'App\Http\Controllers\QuizmeController@chat');

Route::get('/rewriter', 'App\Http\Controllers\RewriterController@showForm');
Route::post('/rewriter', 'App\Http\Controllers\RewriterController@processForm');
