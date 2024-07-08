<?php

use App\Http\Controllers\Api\ExcludingUsersV2Controller;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('refresh', function () {
    $user = User::find(1);
    $user->tokens()->delete();
    $token = $user->createToken('sanctumAuth')->plainTextToken;
    return $this->jsonResponseWithoutMessage($token, 'data', 200);
});
