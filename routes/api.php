<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\MarkController;
use App\Http\Controllers\Api\RejectedMarkController;

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

    ########Mark########
    Route::group(['prefix'=>'mark'], function(){
        Route::get('/', [MarkController::class, 'index']);
        Route::post('/show', [MarkController::class, 'show']);
        Route::post('/update', [MarkController::class, 'update']);
    });
    ########End Mark########

    ########RejectedMark########
    Route::group(['prefix'=>'rejected-mark'], function(){
        Route::get('/', [RejectedMarkController::class, 'index']);
        Route::post('/create', [RejectedMarkController::class, 'create']);
        Route::post('/show', [RejectedMarkController::class, 'show']);
        Route::post('/update', [RejectedMarkController::class, 'update']);
    });
    ########End RejectedMark ########
});