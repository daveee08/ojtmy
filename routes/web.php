<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SummarizeController;
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


Route::get('/', [SummarizeController::class, 'index']);
Route::post('/summarize', [SummarizeController::class, 'summarize']);
Route::view('/tools-hub', 'hub');

