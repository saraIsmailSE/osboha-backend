<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PollVoteController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\ReactionController;
use App\Http\Controllers\Api\SystemIssueController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\MarkController;
use App\Http\Controllers\Api\RejectedMarkController;
use App\Http\Controllers\Api\UserExceptionController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\InfographicController;
use App\Http\Controllers\Api\InfographicSeriesController;
use App\Http\Controllers\Api\SocialMediaController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\ProfileSettingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ThesisController;
use App\Http\Controllers\Api\UserGroupController;
use App\Http\Controllers\Api\RoomController;

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
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    ########Book########
    Route::group(['prefix' => 'book'], function () {
        Route::get('/', [BookController::class, 'index']);
        Route::post('/create', [BookController::class, 'create']);
        Route::post('/show', [BookController::class, 'show']);
        Route::post('/update', [BookController::class, 'update']);
        Route::post('/delete', [BookController::class, 'delete']);
        Route::post('/book-by-type', [BookController::class, 'bookByType']);
        Route::post('/book-by-level', [BookController::class, 'bookByLevel']);
        Route::post('/book-by-section', [BookController::class, 'bookBySection']);
        Route::post('/book-by-name', [BookController::class, 'bookByName']);
    });
    ########End Book########
    ########Start Rate########
    Route::group(['prefix' => 'rate'], function () {
        Route::get('/', [RateController::class, 'index']);
        Route::post('/create', [RateController::class, 'create']);
        Route::post('/show', [RateController::class, 'show']);
        Route::post('/update', [RateController::class, 'update']);
        Route::post('/delete', [RateController::class, 'delete']);
    });
    ########End Rate########
    ########Reaction########
    Route::group(['prefix' => 'reaction'], function () {
        Route::get('/', [ReactionController::class, 'index']);
        Route::post('/create', [ReactionController::class, 'create']);
        Route::post('/show', [ReactionController::class, 'show']);
        Route::post('/update', [ReactionController::class, 'update']);
        Route::post('/delete', [ReactionController::class, 'delete']);
    });
    ########End Reaction########
    ########SystemIssue########
    Route::group(['prefix' => 'system-issue'], function () {
        Route::get('/', [SystemIssueController::class, 'index']);
        Route::post('/create', [SystemIssueController::class, 'create']);
        Route::post('/show', [SystemIssueController::class, 'show']);
        Route::post('/update', [SystemIssueController::class, 'update']);
    });
    ########End SystemIssue########

    ########Transaction########
    Route::group(['prefix' => 'transaction'], function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/create', [TransactionController::class, 'create']);
        Route::post('/show', [TransactionController::class, 'show']);
        Route::post('/show/user/all', [TransactionController::class, 'showUserTransactions']);
        Route::post('/update', [TransactionController::class, 'update']);
    });
    ########End Transaction########
    ########Start Comment########
    Route::group(['prefix' => 'comment'], function () {
        Route::post('/create', [CommentController::class, 'create']);
        Route::post('/show', [CommentController::class, 'show']);
        Route::post('/update', [CommentController::class, 'update']);
        Route::post('/delete', [CommentController::class, 'delete']);
    });
    ########End Comment########
    ########Start Media########
    Route::group(['prefix' => 'media'], function () {
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
    Route::group(['prefix' => 'mark'], function () {
        Route::get('/', [MarkController::class, 'index']);
        Route::post('/show', [MarkController::class, 'show']);
        Route::post('/update', [MarkController::class, 'update']);
        Route::post('/list', [MarkController::class, 'list_user_mark']);
        Route::get('/audit/generate', [MarkController::class, 'generateAuditMarks']);
        Route::get('/audit/leaders', [MarkController::class, 'leadersAuditmarks']);         
        Route::post('/audit/show', [MarkController::class, 'showAuditmarks']);        
        Route::post('/audit/update', [MarkController::class, 'updateAuditMark']);
        //Route::get('/statsmark', [MarkController::class, 'statsMark']);
    });
    ########End Mark########

    ########RejectedMark########
    Route::group(['prefix' => 'rejected-mark'], function () {
        Route::get('/', [RejectedMarkController::class, 'index']);
        Route::post('/create', [RejectedMarkController::class, 'create']);
        Route::post('/show', [RejectedMarkController::class, 'show']);
        Route::post('/update', [RejectedMarkController::class, 'update']);
        Route::post('/list', [RejectedMarkController::class, 'list_user_mark']);
    });
    ########End RejectedMark ########

    #########UserException########
    Route::group(['prefix' => 'userexception'], function () {
        Route::get('/', [UserExceptionController::class, 'index']);
        Route::post('/create', [UserExceptionController::class, 'create']);
        Route::get('/show', [UserExceptionController::class, 'show']);
        Route::post('/update', [UserExceptionController::class, 'update']);
        Route::post('/delete', [UserExceptionController::class, 'delete']);
    });
    ############End UserException########

    ############Group############
    Route::group(['prefix' => 'group'], function () {
        Route::get('/', [GroupController::class, 'index']);
        Route::post('/create', [GroupController::class, 'create']);
        Route::get('/show', [GroupController::class, 'show']);
        Route::post('/GroupByType', [GroupController::class, 'GroupByType']);
        Route::post('/update', [GroupController::class, 'update']);
        Route::post('/delete', [GroupController::class, 'delete']);
    });
    ############End Group############

    ########Start Activity########
    Route::group(['prefix' => 'activity'], function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::post('/create', [ActivityController::class, 'create']);
        Route::post('/show', [ActivityController::class, 'show']);
        Route::post('/update', [ActivityController::class, 'update']);
        Route::post('/delete', [ActivityController::class, 'delete']);
    });
    ########End Activity########

    ########Start Article########
    Route::group(['prefix' => 'article'], function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::post('/create', [ArticleController::class, 'create']);
        Route::post('/show', [ArticleController::class, 'show']);
        Route::post('/update', [ArticleController::class, 'update']);
        Route::post('/delete', [ArticleController::class, 'delete']);
        Route::post('/articles-by-user', [ArticleController::class, 'listAllArticlesByUser']);
    });
    ########End Article########
    ########Start SocialMedia########
    Route::group(['prefix' => 'socialMedia'], function () {
        Route::get('/', [SocialMediaController::class, 'index']);
        Route::post('/create', [SocialMediaController::class, 'create']);
        Route::post('/show', [SocialMediaController::class, 'show']);
        Route::post('/update', [SocialMediaController::class, 'update']);
        Route::post('/delete', [SocialMediaController::class, 'delete']);
    });
    ########End SocialMedia########

    ########Start Timeline ########
    Route::group(['prefix' => 'timeline'], function () {
        Route::get('/', [TimelineController::class, 'index']);
        Route::post('/create', [TimelineController::class, 'create']);
        Route::post('/show', [TimelineController::class, 'show']);
        Route::post('/update', [TimelineController::class, 'update']);
        Route::post('/delete', [TimelineController::class, 'delete']);
    });
    ########End Timeline ########

    ########Start Infographic########
    Route::group(['prefix' => 'infographic'], function () {
        Route::get('/', [InfographicController::class, 'index']);
        Route::post('/create', [InfographicController::class, 'create']);
        Route::post('/show', [InfographicController::class, 'show']);
        Route::post('/update', [InfographicController::class, 'update']);
        Route::post('/delete', [InfographicController::class, 'delete']);
    });
    ########End Infographic ########

    ########Start InfographicSeries########
    Route::group(['prefix' => 'infographicSeries'], function () {
        Route::get('/', [InfographicSeriesController::class, 'index']);
        Route::post('/create', [InfographicSeriesController::class, 'create']);
        Route::post('/show', [InfographicSeriesController::class, 'show']);
        Route::post('/update', [InfographicSeriesController::class, 'update']);
        Route::post('/delete', [InfographicSeriesController::class, 'delete']);
    });
    ########End InfographicSeries########    
    ########Post########
    Route::group(['prefix' => 'post'], function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/create', [PostController::class, 'create']);
        Route::post('/show', [PostController::class, 'show']);
        Route::post('/update', [PostController::class, 'update']);
        Route::post('/delete', [PostController::class, 'delete']);
        Route::post('/postByTimelineId', [PostController::class, 'postByTimelineId']);
        Route::post('/postByUserId', [PostController::class, 'postByUserId']);
        Route::post('/PostsToAccept', [PostController::class, 'listPostsToAccept']);
        Route::post('/acceptPost', [PostController::class, 'AcceptPost']);
        Route::post('/declinePost', [PostController::class, 'declinePost']);
    });
    ########End Post########

    ########Poll-Vote########
    Route::group(['prefix' => 'poll-vote'], function () {
        Route::get('/', [PollVoteController::class, 'index']);
        Route::post('/create', [PollVoteController::class, 'create']);
        Route::post('/show', [PollVoteController::class, 'show']);
        Route::post('/votesByPostId', [PollVoteController::class, 'votesByPostId']);
        Route::post('/votesByAuthUser', [PollVoteController::class, 'votesByAuthUser']);
        Route::post('/votesByUserId', [PollVoteController::class, 'votesByUserId']);
        Route::post('/update', [PollVoteController::class, 'update']);
        Route::post('/delete', [PollVoteController::class, 'delete']);
    });
    ########End Poll-Vote########
    ########User-Profile########
    Route::group(['prefix' => 'user-profile'], function () {
        Route::post('/show', [UserProfileController::class, 'show']);
        Route::post('/update', [UserProfileController::class, 'update']);
    });
    ########End User-Profile########

    ########Profile-Setting########
    Route::group(['prefix' => 'profile-setting'], function () {
        Route::post('/show', [ProfileSettingController::class, 'show']);
        Route::post('/update', [ProfileSettingController::class, 'update']);
    });
    ########End Profile-Setting########
    ####### Notification ########
    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/listAll', [NotificationController::class, 'listAllNotification']);
        Route::get('/unRead', [NotificationController::class, 'listUnreadNotification']);
        Route::get('/makeAllAsRead', [NotificationController::class, 'markAllNotificationAsRead']);
        Route::post('/makeOneAsRead', [NotificationController::class, 'markOneNotificationAsRead']);
    });
    ######## End Notification ########
    ####### UserGroup ########
    Route::group(['prefix' => 'userGroup'], function () {
        Route::get('/', [UserGroupController::class, 'index']);
        Route::post('/show', [UserGroupController::class, 'show']);
        Route::post('/assignRole', [UserGroupController::class, 'assign_role']);
        Route::post('/updateRole', [UserGroupController::class, 'update_role']);
        Route::post('/listUserGroup', [UserGroupController::class, 'list_user_group']);
    });
    ######## UserGroup ########
    ####### thesis ########
    Route::group(['prefix' => 'thesis'], function () {
        Route::get('/', [ThesisController::class, 'index']);
        Route::post('/show', [ThesisController::class, 'show']);
        Route::post('/create', [ThesisController::class, 'create']);
        Route::post('/update', [ThesisController::class, 'update']);
        Route::post('/delete', [ThesisController::class, 'delete']);
        Route::post('/listBookThesis', [ThesisController::class, 'list_book_thesis']);
        Route::post('/listUserThesis', [ThesisController::class, 'list_user_thesis']);
        Route::post('/listWeekThesis', [ThesisController::class, 'list_week_thesis']);
    });
    ######## thesis ########
    ######## Room ########
    Route::group(['prefix'=>'room'], function(){
        Route::post('/create', [RoomController::class, 'create']);       
        Route::post('/addUserToRoom', [RoomController::class, 'addUserToRoom']);        
    });
    ######## Room ########

});