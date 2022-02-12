<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ArticleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\SocialMediaController;
use App\Http\Controllers\Api\TimelineController;



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
    Route::group(['prefix'=>'activity'], function(){
        Route::get('/', [ActivityController::class, 'index']);
        Route::post('/create', [ActivityController::class, 'create']);
        Route::post('/show', [ActivityController::class, 'show']);
        Route::post('/update', [ActivityController::class, 'update']);
        Route::post('/delete', [ActivityController::class, 'delete']);

    });
    ########End Activity########

    ########End Article########
    Route::group(['prefix'=>'article'], function(){
        Route::get('/', [ArticleController::class, 'index']);
        Route::post('/create', [ArticleController::class, 'create']);
        Route::post('/show', [ArticleController::class, 'show']);
        Route::post('/update', [ArticleController::class, 'update']);
        Route::post('/delete', [ArticleController::class, 'delete']);
    });
    ########End Article########
      ########start socialMedia route########
    Route::group(['prefix'=>'socialMedia'], function(){
        Route::get('/', [SocialMediaController::class, 'index']);
        Route::post('/create', [SocialMediaController::class, 'create']);
        Route::post('/show', [SocialMediaController::class, 'show']);
        Route::post('/update', [SocialMediaController::class, 'update']);
        Route::post('/delete', [SocialMediaController::class, 'delete']);

    });
    ########end socialMedia route########

    ########start timeline route########
    Route::group(['prefix'=>'timeline'], function(){
        Route::get('/', [TimelineController::class, 'index']);
        Route::post('/create', [TimelineController::class, 'create']);
        Route::post('/show', [TimelineController::class, 'show']);
        Route::post('/update', [TimelineController::class, 'update']);
        Route::post('/delete', [TimelineController::class, 'delete']);

    });
    ########end timeline route########
});

   