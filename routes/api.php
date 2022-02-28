<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\ReactionController;
use App\Http\Controllers\Api\SystemIssueController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\MarkController;
use App\Http\Controllers\Api\RejectedMarkController;
use App\Http\Controllers\Api\UserExceptionController;
use App\Http\Controllers\Api\GroupController;
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
    ########Start Rate########
    Route::group(['prefix'=>'rate'], function(){
        Route::get('/', [RateController::class, 'index']);
        Route::post('/create', [RateController::class, 'create']);
        Route::post('/show', [RateController::class, 'show']);
        Route::post('/update', [RateController::class, 'update']);
        Route::post('/delete', [RateController::class, 'delete']);

    });
    ########End Rate########
    ########Reaction########
    Route::group(['prefix'=>'reaction'], function(){
        Route::get('/', [ReactionController::class, 'index']);
        Route::post('/create', [ReactionController::class, 'create']);
        Route::post('/show', [ReactionController::class, 'show']);
        Route::post('/update', [ReactionController::class, 'update']);
        Route::post('/delete', [ReactionController::class, 'delete']);

    });
    ########End Reaction########
    ########SystemIssue########
    Route::group(['prefix'=>'system-issue'], function(){
        Route::get('/', [SystemIssueController::class, 'index']);
        Route::post('/create', [SystemIssueController::class, 'create']);
        Route::post('/show', [SystemIssueController::class, 'show']);
        Route::post('/update', [SystemIssueController::class, 'update']);
    });
    ########End SystemIssue########

    ########Transaction########
    Route::group(['prefix'=>'transaction'], function(){
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/create', [TransactionController::class, 'create']);
        Route::post('/show', [TransactionController::class, 'show']);
        Route::post('/update', [TransactionController::class, 'update']);
    });
    ########End Transaction########
    ########Start Comment########
    Route::group(['prefix'=>'comment'], function(){
        Route::post('/create', [CommentController::class, 'create']);
        Route::post('/show', [CommentController::class, 'show']);
        Route::post('/update', [CommentController::class, 'update']);
        Route::post('/delete', [CommentController::class, 'delete']);
    });
    ########End Comment########
    ########Start Media########
    Route::group(['prefix'=>'media'], function(){
        Route::get('/', [MediaController::class, 'index']);
        Route::post('/create', [MediaController::class, 'create']);
        Route::post('/show', [MediaController::class, 'show']);
        Route::post('/update', [mediaController::class, 'update']);
        Route::post('/delete', [MediaController::class, 'delete']);
    });
    ########End Media route########
    ########Start Friend route########
    Route::group(['prefix' => 'friend'], function () {
        Route::get('/', [FriendController::class, 'index']);
        Route::post('/create', [FriendController::class, 'create']);
        Route::post('/show', [FriendController::class, 'show']);
        Route::post('/update', [FriendController::class, 'update']);
        Route::post('/delete', [FriendController::class, 'delete']);
    });
    ########End Friend route########
    ########Mark########
    Route::group(['prefix'=>'mark'], function(){
        Route::get('/', [MarkController::class, 'index']);
        Route::post('/show', [MarkController::class, 'show']);
        Route::post('/update', [MarkController::class, 'update']);
        Route::post('/user', [MarkController::class, 'marks_by_userid']);
        Route::post('/week', [MarkController::class, 'marks_by_weekid']);
        Route::post('/user-week', [MarkController::class, 'marks_by_userid_and_weekid']);
    });
    ########End Mark########

    ########RejectedMark########
    Route::group(['prefix'=>'rejected-mark'], function(){
        Route::get('/', [RejectedMarkController::class, 'index']);
        Route::post('/create', [RejectedMarkController::class, 'create']);
        Route::post('/show', [RejectedMarkController::class, 'show']);
        Route::post('/update', [RejectedMarkController::class, 'update']);
        Route::get('/user', [RejectedMarkController::class, 'rejectedmarks_by_userid']);
        Route::get('/week', [RejectedMarkController::class, 'rejectedmarks_by_weekid']);
        Route::get('/user-week', [RejectedMarkController::class, 'rejectedmarks_by_userid_and_weekid']);
    });
    ########End RejectedMark ########
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
    ########Start SocialMedia########
    Route::group(['prefix'=>'socialMedia'], function(){
        Route::get('/', [SocialMediaController::class, 'index']);
        Route::post('/create', [SocialMediaController::class, 'create']);
        Route::post('/show', [SocialMediaController::class, 'show']);
        Route::post('/update', [SocialMediaController::class, 'update']);
        Route::post('/delete', [SocialMediaController::class, 'delete']);

    });
    ########End SocialMedia########

    ########Start Timeline ########
    Route::group(['prefix'=>'timeline'], function(){
        Route::get('/', [TimelineController::class, 'index']);
        Route::post('/create', [TimelineController::class, 'create']);
        Route::post('/show', [TimelineController::class, 'show']);
        Route::post('/update', [TimelineController::class, 'update']);
        Route::post('/delete', [TimelineController::class, 'delete']);

    });
    ########End Timeline ########
});
