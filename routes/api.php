<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\ReactionController;



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
});
Route::middleware('auth:sanctum')->group( function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    ########Rate########
    Route::group(['prefix'=>'rate'], function(){
        Route::get('/', [RateController::class, 'index']);
        Route::post('/create', [RateController::class, 'create']);
        Route::post('/show', [RateController::class, 'show']);
        Route::post('/update', [RateController::class, 'update']);
        Route::post('/delete', [RateController::class, 'delete']);

    });
    ########End Rate########
});
Route::middleware('auth:sanctum')->group( function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    ########Reaction########
    Route::group(['prefix'=>'reaction'], function(){
        Route::get('/', [ReactionController::class, 'index']);
        Route::post('/create', [ReactionController::class, 'create']);
        Route::post('/show', [ReactionController::class, 'show']);
        Route::post('/update', [ReactionController::class, 'update']);
        Route::post('/delete', [ReactionController::class, 'delete']);

    });
    ########End Reaction########
});