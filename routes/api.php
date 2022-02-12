<?php

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
    #Start Media route#
    Route::group(['prefix'=>'media'], function(){
        Route::get('/', [MediaController::class, 'index']);
        Route::post('/create', [MediaController::class, 'create']);
        Route::post('/show', [MediaController::class, 'show']);
        Route::post('/update', [mediaController::class, 'update']);
        Route::post('/delete', [MediaController::class, 'delete']);
    });
#End Media route#
#Start Friend route#
Route::group(['prefix'=>'friend'], function(){
    Route::get('/', [FriendController::class, 'index']);
    Route::post('/create', [FriendController::class, 'create']);
    Route::post('/show', [FriendController::class, 'show']);
    Route::post('/update', [FriendController::class, 'update']);
    Route::post('/delete', [FriendController::class, 'delete']);

});

});