<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
<<<<<<< HEAD
use App\Http\Controllers\Api\UserExceptionController;
use App\Http\Resources\UserException;
=======
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;


>>>>>>> 4e3cd04f72e527e0b4f36bb9f3d972fef93067fe

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

<<<<<<< HEAD
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
=======
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
>>>>>>> 4e3cd04f72e527e0b4f36bb9f3d972fef93067fe
