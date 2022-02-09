<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\UserExceptionController;
use App\Http\Controllers\Api\GroupController;


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

    #########UserException########
    Route::group(['prefix' => 'userexception'], function(){
        Route::get('/',[UserExceptionController::class,'index']);
        Route::post('/create',[UserExceptionController::class,'create']);
        Route::get('/show',[UserExceptionController::class,'show']);
        Route::post('/update',[UserExceptionController::class,'update']);
    });
    ############End UserException########

    ############Group############
    Route::group(['prefix' => 'group'], function(){
        Route::get('/',[GroupController::class,'index']);
        Route::post('/create',[GroupController::class,'create']);
        Route::get('/show',[GroupController::class,'show']);
        Route::post('/update',[GroupController::class,'update']);
        Route::post('/delete',[GroupController::class,'delete']);
    });
    ############End Group############
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});