<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\SystemIssueController;
use App\Http\Controllers\Api\TransactionController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group( function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    ########Book########
    Route::group(['prefix'=>'book'], function(){
        Route::get('/', [BookController::class, 'index']);
        Route::post('/create', [BookController::class, 'create']);
        Route::post('/show', [BookController::class, 'show']);
        Route::post('/update', [BookController::class, 'update']);
        Route::post('/delete', [BookController::class, 'delete']);

    });
    ########End Book########

    ########SystemIssue########
    Route::group(['prefix'=>'system-issue'], function(){
        Route::get('/', [SystemIssueController::class, 'index']);
        Route::post('/create', [SystemIssueController::class, 'create']);
        Route::post('/show', [SystemIssueController::class, 'show']);
        Route::post('/update', [SystemIssueController::class, 'update']);
    });
    ########End SystemIssue########

    ########Transaction########
    Route::group(['prefix'=>'transaction'], function(){
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/create', [TransactionController::class, 'create']);
        Route::post('/show', [TransactionController::class, 'show']);
        Route::post('/update', [TransactionController::class, 'update']);
    });
    ########End Transaction########
});