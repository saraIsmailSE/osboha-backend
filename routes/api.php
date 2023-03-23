<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BookStatisticsController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PollVoteController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\ReactionController;
use App\Http\Controllers\Api\LeaderRequestController;
use App\Http\Controllers\Api\HighPriorityRequestController;
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
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\BooktypeController;
use App\Http\Controllers\Api\ExceptionTypeController;
use App\Http\Controllers\Api\GroupTypeController;
use App\Http\Controllers\Api\PostTypeController;
use App\Http\Controllers\Api\ThesisTypeController;
use App\Http\Controllers\Api\TimelineTypeController;
use App\Http\Controllers\Api\RejectedThesesController;
use App\Http\Controllers\api\WeekController;
use App\Http\Controllers\Api\MessagesController;
use App\Http\Controllers\Api\UserBookController;



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


Route::group(['prefix' => 'v1'], function () {
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
            Route::post('/book-by-language', [BookController::class, 'bookByLanguage']);
            Route::get('/recent-added-books', [BookController::class, 'getRecentAddedBooks']);
            Route::get('/most-readable-books', [BookController::class, 'getMostReadableBooks']);
            Route::get('/random-book', [BookController::class, 'getRandomBook']);
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
        ########LeaderRequest########
        Route::group(['prefix' => 'leader-request'], function () {
            Route::get('/', [LeaderRequestController::class, 'index']);
            Route::post('/create', [LeaderRequestController::class, 'create']);
            Route::post('/show', [LeaderRequestController::class, 'show']);
            Route::post('/update', [LeaderRequestController::class, 'update']);
        });
        ########End LeaderRequest########
        ########HighPriorityRequest########
        Route::group(['prefix' => 'high-priority-request'], function () {
            Route::get('/', [HighPriorityRequestController::class, 'index']);
            Route::post('/create', [HighPriorityRequestController::class, 'create']);
            Route::post('/show', [HighPriorityRequestController::class, 'show']);
        });
        ########End HighPriorityRequest########
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
            Route::post('/get-post-comments', [CommentController::class, 'getPostComments']);
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
        Route::group(['prefix' => 'friends'], function () {
            Route::get('/user/{user_id}', [FriendController::class, 'listByUserId'])->where('user_id', '[0-9]+');
            Route::post('/', [FriendController::class, 'create']);
            Route::get('/{friendship-id}', [FriendController::class, 'show'])->where('friendship-id', '[0-9]+');
            Route::patch('/accept-friend-request/{friendship-id}', [FriendController::class, 'accept']);
            Route::delete('/{friendship-id}', [FriendController::class, 'delete']);
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
            Route::post('/list', [RejectedMarkController::class, 'list_user_rejectedmark']);
        });
        ########End RejectedMark ########
        ########RejectedTheses########
        Route::group(['prefix' => 'rejected-theses'], function () {
            Route::get('/', [RejectedThesesController::class, 'index']);
            Route::post('/create', [RejectedThesesController::class, 'create']);
            Route::post('/show', [RejectedThesesController::class, 'show']);
            Route::post('/update', [RejectedThesesController::class, 'update']);
            Route::post('/list', [RejectedThesesController::class, 'list_user_rejectedtheses']);
        });
        ########End RejectedTheses ########
        #########UserException########
        Route::group(['prefix' => 'userexception'], function () {
            Route::post('/groupExceptions', [UserExceptionController::class, 'groupExceptions']);
            Route::post('/create', [UserExceptionController::class, 'create']);
            Route::get('/show', [UserExceptionController::class, 'show']);
            Route::post('/update', [UserExceptionController::class, 'update']);
            Route::post('/delete', [UserExceptionController::class, 'delete']);
            Route::post('/updateStatus', [UserExceptionController::class, 'updateStatus']);
            Route::get('/listPindigExceptions', [UserExceptionController::class, 'listPindigExceptions']);
        });
        ############End UserException########

        ############Group############
        Route::group(['prefix' => 'group'], function () {
            Route::get('/', [GroupController::class, 'index']);
            Route::post('/create', [GroupController::class, 'create']);
            Route::get('/show/{group_id}', [GroupController::class, 'show']);
            Route::post('/GroupByType', [GroupController::class, 'GroupByType']);
            Route::post('/update', [GroupController::class, 'update']);
            Route::post('/delete', [GroupController::class, 'delete']);
            Route::get('/books/{group_id}', [GroupController::class, 'books']);
            Route::get('/groupExceptions/{group_id}', [GroupController::class, 'groupExceptions']);
        });

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
            Route::post('/add-social-media', [SocialMediaController::class, 'addSocialMedia']);
            Route::post('/show', [SocialMediaController::class, 'show']);
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
            Route::post('/infographicBySection', [InfographicController::class, 'InfographicBySection']);
        });
        ########End Infographic ########

        ########Start InfographicSeries########
        Route::group(['prefix' => 'infographicSeries'], function () {
            Route::get('/', [InfographicSeriesController::class, 'index']);
            Route::post('/create', [InfographicSeriesController::class, 'create']);
            Route::post('/show', [InfographicSeriesController::class, 'show']);
            Route::post('/update', [InfographicSeriesController::class, 'update']);
            Route::post('/delete', [InfographicSeriesController::class, 'delete']);
            Route::post('/seriesBySection', [InfographicSeriesController::class, 'SeriesBySection']);
        });
        ########End InfographicSeries########    
        ########Start Post########
        #updated RESTful routes by asmaa#
        Route::group(['prefix' => 'posts'], function () {
            Route::get('/', [PostController::class, 'index']);
            Route::post('/', [PostController::class, 'create']);
            Route::get('/{id}', [PostController::class, 'show'])->where('id', '[0-9]+');
            Route::put('/{id}', [PostController::class, 'update']);
            Route::delete('/{id}', [PostController::class, 'delete']);
            Route::get('/timelines/{timeline-id}', [PostController::class, 'postByTimelineId'])->where('timeline-id', '[0-9]+');
            Route::get('/users/{user-id}', [PostController::class, 'postByUserId'])->where('user-id', '[0-9]+');
            Route::get('/pending/timelines/{timeline-id}', [PostController::class, 'listPostsToAccept'])->where('timeline-id', '[0-9]+');
            Route::get('/accept-post/{id}', [PostController::class, 'acceptPost'])->where('id', '[0-9]+');
            Route::get('/decline-post/{id}', [PostController::class, 'declinePost'])->where('id', '[0-9]+');
            Route::patch('/{id}/control-comments', [PostController::class, 'controlComments']);
            Route::get('/pin-post/{id}', [PostController::class, 'pinPost'])->where('id', '[0-9]+');
        });
        ########End Post########

        ########Poll-Vote########
        Route::group(['prefix' => 'poll-vote'], function () {
            Route::get('/', [PollVoteController::class, 'index']);
            Route::post('/create', [PollVoteController::class, 'create']);
            Route::post('/show', [PollVoteController::class, 'show']);
            Route::post('/votesByPostId', [PollVoteController::class, 'votesByPostId']);
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
            Route::post('/show', [ThesisController::class, 'show']);
            Route::post('/listBookThesis', [ThesisController::class, 'list_book_thesis']);
            Route::post('/listUserThesis', [ThesisController::class, 'list_user_thesis']);
            Route::post('/listWeekThesis', [ThesisController::class, 'list_week_thesis']);
        });
        ######## thesis ########
        ######## Room ########
        Route::group(['prefix' => 'room'], function () {
            Route::post('/create', [RoomController::class, 'create']);
            Route::post('/addUserToRoom', [RoomController::class, 'addUserToRoom']);
        });
        ######## Room ########
        ######## Week ########
        Route::group(['prefix' => 'week'], function () {
            Route::post('/create', [WeekController::class, 'create']);
            Route::post('/update', [WeekController::class, 'update']);
            Route::post('/get_last_weeks_ids', [WeekController::class, 'get_last_weeks_ids']); //for testing - to be deleted
        });
        ######## Week ########

        ######## Section ########
        Route::group(['prefix' => 'section'], function () {
            Route::get('/', [SectionController::class, 'index']);
            Route::post('/create', [SectionController::class, 'create']);
            Route::post('/show', [SectionController::class, 'show']);
            Route::post('/update', [SectionController::class, 'update']);
            Route::post('/delete', [SectionController::class, 'delete']);
        });
        ######## Section ########
        ######## Book-Type ########
        Route::group(['prefix' => 'book-type'], function () {
            Route::get('/', [BookTypeController::class, 'index']);
            Route::post('/create', [BookTypeController::class, 'create']);
            Route::post('/show', [BookTypeController::class, 'show']);
            Route::post('/update', [BookTypeController::class, 'update']);
            Route::post('/delete', [BookTypeController::class, 'delete']);
        });
        ######## Book-Type ########

        ######## Exception-Type ########
        Route::group(['prefix' => 'exception-type'], function () {
            Route::get('/', [ExceptionTypeController::class, 'index']);
            Route::post('/create', [ExceptionTypeController::class, 'create']);
            Route::post('/show', [ExceptionTypeController::class, 'show']);
            Route::post('/update', [ExceptionTypeController::class, 'update']);
            Route::post('/delete', [ExceptionTypeController::class, 'delete']);
        });
        ######## Exception-Type ########

        ######## Group-Type ########
        Route::group(['prefix' => 'group-type'], function () {
            Route::get('/', [GroupTypeController::class, 'index']);
            Route::post('/create', [GroupTypeController::class, 'create']);
            Route::post('/show', [GroupTypeController::class, 'show']);
            Route::post('/update', [GroupTypeController::class, 'update']);
            Route::post('/delete', [GroupTypeController::class, 'delete']);
        });
        ######## Group-Type ########

        ######## Post-Type ########
        Route::group(['prefix' => 'post-type'], function () {
            Route::get('/', [PostTypeController::class, 'index']);
            Route::post('/create', [PostTypeController::class, 'create']);
            Route::post('/show', [PostTypeController::class, 'show']);
            Route::post('/update', [PostTypeController::class, 'update']);
            Route::post('/delete', [PostTypeController::class, 'delete']);
        });
        ######## Post-Type ########

        ######## Thesis-Type ########
        Route::group(['prefix' => 'thesis-type'], function () {
            Route::get('/', [ThesisTypeController::class, 'index']);
            Route::post('/create', [ThesisTypeController::class, 'create']);
            Route::post('/show', [ThesisTypeController::class, 'show']);
            Route::post('/update', [ThesisTypeController::class, 'update']);
            Route::post('/delete', [ThesisTypeController::class, 'delete']);
        });
        ######## Thesis-Type ########

        ######## Timeline-Type ########
        Route::group(['prefix' => 'timeline-type'], function () {
            Route::get('/', [TimelineTypeController::class, 'index']);
            Route::post('/create', [TimelineTypeController::class, 'create']);
            Route::post('/show', [TimelineTypeController::class, 'show']);
            Route::post('/update', [TimelineTypeController::class, 'update']);
            Route::post('/delete', [TimelineTypeController::class, 'delete']);
        });
        ######## Timeline-Type ########
        ######## Messages ########
        Route::get('listAllMessages', [MessagesController::class, 'listAllMessages']);
        Route::post('/updateStatus', [MessagesController::class, 'updateStatus']);
        Route::post('/sendMessage', [MessagesController::class, 'sendMessage']);
        Route::post('/listMessage', [MessagesController::class, 'listMessage']);
        Route::post('/listRoomMessages', [MessagesController::class, 'listRoomMessages']);
        ######## Messages ########
        ######## BookStatistics ########
        Route::group(['prefix' => 'book-stat'], function () {
            Route::get('/', [BookStatisticsController::class, 'index']);
        });
        ######## BookStatistics ########

    });
});