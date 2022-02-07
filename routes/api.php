<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserExceptionController;

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

/***** Route Group for UserExceptionController ******/
Route::group([
    'prefix' => 'userexception',
    'as' => 'userexception.'
 ], function(){

    Route::get('/',[UserExceptionController::class,'index'])->name('index');
    Route::post('/create',[UserExceptionController::class,'store'])->name('create');


}); //end of UserExceptionController Route Group

