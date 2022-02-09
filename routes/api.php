<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ArticleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;



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

    ########Activity########
    #######ASMAA#######
    Route::group(['prefix'=>'activity'], function(){
        Route::get('/', [ActivityController::class, 'index']);
        Route::post('/create', [ActivityController::class, 'create']);
        Route::post('/show', [ActivityController::class, 'show']);
        Route::post('/update', [ActivityController::class, 'update']);
        Route::post('/delete', [ActivityController::class, 'delete']);

    });
    ########End Activity########

    ########End Article########
    #######ASMAA#######
    Route::group(['prefix'=>'article'], function(){
        Route::get('/', [ArticleController::class, 'index']);
        Route::post('/create', [ArticleController::class, 'create']);
        Route::post('/show', [ArticleController::class, 'show']);
        Route::post('/update', [ArticleController::class, 'update']);
        Route::post('/delete', [ArticleController::class, 'delete']);
    });
    ########End Article########
});