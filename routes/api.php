<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserExceptionController;
use App\Http\Resources\UserException;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

############ UserException ############

Route::group(['prefix' => 'userexception'], function(){
    Route::get('/',[UserExceptionController::class,'index']);
    Route::post('/create',[UserExceptionController::class,'store']);
    Route::get('/show/{id}',[UserExceptionController::class,'show']);
    Route::post('/update/{id}',[UserExceptionController::class,'update']);
});

############ End UserException ############
