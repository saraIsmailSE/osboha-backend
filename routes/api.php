<?php

use App\Events\NotificationsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Api\{
    ActivityController,
    ArticleController,
    Auth\AuthController,
    Auth\EmailVerificationController,
    BookController,
    BookStatisticsController,
    PostController,
    PollVoteController,
    RateController,
    ReactionController,
    LeaderRequestController,
    HighPriorityRequestController,
    SystemIssueController,
    TransactionController,
    CommentController,
    MarkController,
    AuditMarkController,
    RejectedMarkController,
    UserExceptionController,
    GroupController,
    InfographicController,
    InfographicSeriesController,
    SocialMediaController,
    TimelineController,
    FriendController,
    UserProfileController,
    ProfileSettingController,
    NotificationController,
    ThesisController,
    UserGroupController,
    RoomController,
    SectionController,
    //BooktypeController,
    ExceptionTypeController,
    GroupTypeController,
    MediaController,
    PostTypeController,
    ThesisTypeController,
    TimelineTypeController,
    RejectedThesesController,
    WeekController,
    MessagesController,
    ModificationReasonController,
    ModifiedThesesController,
    UserBookController
};

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
    Route::post('/register', [AuthController::class, 'signUp']);

    Route::get('/profile-image/{profile_id}/{file_name}', [UserProfileController::class, 'getImages'])->where('file_name', '.*');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('password/forgot-password', [AuthController::class, 'sendResetLinkResponse'])->name('passwords.sent');
    Route::post('password/reset', [AuthController::class, 'sendResetResponse'])->name('passwords.reset');


    Route::middleware('auth:sanctum', 'verified', 'IsActiveUser')->group(function () {

        Route::post('email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
        Route::get('/myTEST', function () {
            $user = User::find(1);
            $msg = "لديك طلب صداقة ";
            (new NotificationController)->sendNotification($user->id, $msg, 'friends');

            // $test = 'اشعار جديد';
            // $user=User::find(Auth::id());
            // event(new NotificationsEvent($test,$user));

        });

        Route::post('/refresh', [AuthController::class, 'refresh']);

        Route::post('/assign-role', [AuthController::class, 'assignRole']);
        Route::get('/get-roles/{id}', [AuthController::class, 'getRoles']);
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/session-data', [AuthController::class, 'sessionData']);
        Route::post('email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
        ########Book########
        Route::group(['prefix' => 'books'], function () {
            Route::get('/', [BookController::class, 'index']);
            Route::post('/', [BookController::class, 'create']);
            Route::get('/{id}', [BookController::class, 'show'])->where('id', '[0-9]+');
            Route::put('/', [BookController::class, 'update']);
            Route::delete('/{id}', [BookController::class, 'delete']);
            Route::get('/type/{type_id}', [BookController::class, 'bookByType'])->where('type_id', '[0-9]+');
            Route::get('/level/{level}', [BookController::class, 'bookByLevel']);
            Route::get('/section/{section_id}', [BookController::class, 'bookBySection'])->where('section_id', '[0-9]+');
            Route::post('/name', [BookController::class, 'bookByName']);
            Route::get('/language/{language}', [BookController::class, 'bookByLanguage']);
            Route::get('/recent-added-books', [BookController::class, 'getRecentAddedBooks']);
            Route::get('/most-readable-books', [BookController::class, 'getMostReadableBooks']);
            Route::get('/random-book', [BookController::class, 'getRandomBook']);
            Route::get('/latest', [BookController::class, 'latest']);
        });
        ########End Book########
        ########User Book########
        Route::group(['prefix' => 'user-books'], function () {
            Route::get('/show/{user_id}', [UserBookController::class, 'show']);
            Route::get('/later-books/{user_id}', [UserBookController::class, 'later']);
            Route::post('/update', [UserBookController::class, 'update']);
            Route::delete('/{id}', [UserBookController::class, 'delete']);
            Route::patch('{id}/save-for-later/', [UserBookController::class, 'saveBookForLater']);
        });
        ########End User Book########
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
        Route::group(['prefix' => 'reactions'], function () {
            Route::get('/', [ReactionController::class, 'index']);
            Route::post('/create', [ReactionController::class, 'create']);
            Route::post('/show', [ReactionController::class, 'show']);
            Route::post('/update', [ReactionController::class, 'update']);
            Route::post('/delete', [ReactionController::class, 'delete']);
            Route::get('/types', [ReactionController::class, 'getReactionTypes']);
            Route::get('/posts/{post_id}/types/{type_id}', [ReactionController::class, 'reactOnPost'])->where('post_id', '[0-9]+')->where('type_id', '[0-9]+');
            Route::get('/comments/{comment_id}/types/{type_id}', [ReactionController::class, 'reactOnComment'])->where('comment_id', '[0-9]+')->where('type_id', '[0-9]+');
            Route::get('/posts/{post_id}/users/{user_id?}', [ReactionController::class, 'getPostReactionsUsers'])->where('post_id', '[0-9]+')->where('user_id', '[0-9]+');
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

        ########Start Comment########
        Route::group(['prefix' => 'comments'], function () {
            Route::post('/', [CommentController::class, 'create']);
            Route::get('/post/{post_id}/{user_id?}', [CommentController::class, 'getPostComments'])->where('post_id', '[0-9]+')->where('user_id', '[0-9]+');
            Route::post('/update', [CommentController::class, 'update']); //for testing
            Route::put('/', [CommentController::class, 'update']); //gives errors from axios
            Route::delete('/{id}', [CommentController::class, 'delete']);
            Route::get('/post/{post_id}/users', [CommentController::class, 'getPostCommentsUsers'])->where('post_id', '[0-9]+');
        });
        ########End Comment########
        ########Start Media########
        // Route::group(['prefix' => 'media'], function () {
        //     Route::get('/', [MediaController::class, 'index']);
        //     Route::post('/create', [MediaController::class, 'create']);
        //     Route::post('/show', [MediaController::class, 'show']);
        //     Route::post('/update', [mediaController::class, 'update']);
        //     Route::post('/delete', [MediaController::class, 'delete']);
        // });
        ########End Media route########
        ########Start Friend route########
        Route::group(['prefix' => 'friends'], function () {
            Route::get('/user/{user_id}', [FriendController::class, 'listByUserId'])->where('user_id', '[0-9]+');
            Route::get('/accepted/{user_id}', [FriendController::class, 'listByUserId']);
            Route::get('/un-accepted', [FriendController::class, 'listUnAccepted']);
            Route::post('/create', [FriendController::class, 'create']);
            Route::get('/{friendship-id}', [FriendController::class, 'show'])->where('friendship-id', '[0-9]+');
            Route::patch('/accept-friend-request/{friendship-id}', [FriendController::class, 'accept']);
            Route::delete('/{friendship-id}', [FriendController::class, 'delete']);
            Route::get('/accept/{friendship_id}', [FriendController::class, 'accept']);
            Route::post('/delete', [FriendController::class, 'delete']);
            Route::get('/show/{friendship_id}', [FriendController::class, 'show']);
        });
        ########End Friend route########
        ########Mark########
        Route::group(['prefix' => 'marks'], function () {
            Route::get('/', [MarkController::class, 'index']);
            Route::post('/update', [MarkController::class, 'update']);
            Route::post('/list', [MarkController::class, 'list_user_mark']);
            Route::get('/audit/leaders', [MarkController::class, 'leadersAuditmarks']);
            Route::post('/audit/show', [MarkController::class, 'showAuditmarks']);
            Route::post('/audit/update', [MarkController::class, 'updateAuditMark']);
            //Route::get('/statsmark', [MarkController::class, 'statsMark']);
            Route::get('/user-month-achievement/{user_id}/{filter}', [MarkController::class, 'userMonthAchievement']);
            Route::get('/user-week-achievement/{user_id}/{filter}', [MarkController::class, 'userWeekAchievement']);
            Route::get('/ambassador-mark/{user_id}', [MarkController::class, 'ambassadorMark']);
            Route::put('/accept-support/user/{user_id}', [MarkController::class, 'acceptSupport']);
            Route::put('/reject-support/user/{user_id}', [MarkController::class, 'rejectSupport']);
        });
        ########End Mark########

        ######## Start Audit Mark ########
        Route::group(['prefix' => 'audit-marks'], function () {
            Route::get('/generate', [AuditMarkController::class, 'generateAuditMarks']);
            Route::get('/mark-for-audit/{mark_for_audit_id}', [AuditMarkController::class, 'markForAudit']);
            Route::get('/group-audit-marks/{group_id}', [AuditMarkController::class, 'groupAuditMarks']);
            Route::patch('/update-mark-for-audit-status/{id}', [AuditMarkController::class, 'updateMarkForAuditStatus']);
            Route::get('/groups-audit/{supervisor_id}', [AuditMarkController::class, 'groupsAudit']);
            Route::get('/supervisors-audit/{advisor_id}', [AuditMarkController::class, 'allSupervisorsForAdvisor']);
        });
        ######## End Audit Mark ########
        ########Modified Theses########
        Route::group(['prefix' => 'modified-theses'], function () {
            Route::get('/', [ModifiedThesesController::class, 'index']);
            Route::post('/', [ModifiedThesesController::class, 'create']);
            Route::get('/{id}', [ModifiedThesesController::class, 'show'])->where('id', '[0-9]+');
            Route::put('/', [ModifiedThesesController::class, 'update']);
            Route::get('/user/{user_id}', [ModifiedThesesController::class, 'listUserModifiedtheses'])->where('user_id', '[0-9]+');
            Route::get('/week/{week_id}', [ModifiedThesesController::class, 'listModifiedthesesByWeek'])->where('week_id', '[0-9]+');
            Route::get('/user/{user_id}/week/{week_id}', [ModifiedThesesController::class, 'listUserModifiedthesesByWeek'])->where('user_id', '[0-9]+')->where('week_id', '[0-9]+');
        });
        ########End Modified Theses ########

        ########Start ModificationReasons ########
        Route::group(['prefix' => 'modification-reasons'], function () {
            Route::get('/leader', [ModificationReasonController::class, 'getReasonsForLeader']);
        });
        ########End ModificationReasons ########

        #########UserException########
        Route::group(['prefix' => 'userexception'], function () {
            Route::post('/create', [UserExceptionController::class, 'create']);
            Route::get('/show/{exception_id}', [UserExceptionController::class, 'show']);
            Route::post('/update', [UserExceptionController::class, 'update']);
            Route::get('/cancel/{exception_id}', [UserExceptionController::class, 'cancelException']);
            Route::patch('/update-status/{exception_id}', [UserExceptionController::class, 'updateStatus']);
            Route::get('/listPindigExceptions', [UserExceptionController::class, 'listPindigExceptions']);
            Route::post('/addExceptions', [UserExceptionController::class, 'addExceptions']);
            Route::get('/finishedException', [UserExceptionController::class, 'finishedException']);
            Route::get('/user-exceptions/{user_id}', [UserExceptionController::class, 'userExceptions']);
            Route::get('/exceptions-filter/{filter}/{user_id}', [UserExceptionController::class, 'exceptionsFilter']);

        });
        ############End UserException########

        ############ Start Group ############
        Route::group(['prefix' => 'group'], function () {
            Route::get('/', [GroupController::class, 'index']);
            Route::post('/create', [GroupController::class, 'create']);
            Route::get('/show/{group_id}', [GroupController::class, 'show']);
            Route::post('/GroupByType', [GroupController::class, 'GroupByType']);
            Route::post('/update', [GroupController::class, 'update']);
            Route::delete('/delete/{group_id}', [GroupController::class, 'delete']);
            Route::get('/books/{group_id}', [GroupController::class, 'books']);
            Route::get('/group-exceptions/{group_id}', [GroupController::class, 'groupExceptions']);
            Route::get('/exceptions-filter/{filter}/{group_id}', [GroupController::class, 'exceptionsFilter']);
            Route::get('/basic-mark-view/{group_id}', [GroupController::class, 'BasicMarksView']);
            Route::get('/all-achievements/{group_id}/{week_filter?}', [GroupController::class, 'allAchievements']);
            Route::get('/search-for-ambassador-achievement/{ambassador_name}/{group_id}/{week_filter?}', [GroupController::class, 'searchForAmbassadorAchievement']);
            Route::get('/search-for-ambassador/{ambassador_name}/{group_id}', [GroupController::class, 'searchForAmbassador']);
            Route::get('/achievement-as-pages/{group_id}/{week_filter?}', [GroupController::class, 'achievementAsPages']);
            Route::post('/create-leader-request', [GroupController::class, 'createLeaderRequest']);
            Route::get('/last-leader-request/{group_id}', [GroupController::class, 'lastLeaderRequest']);
            Route::get('/audit-marks/{group_id}', [GroupController::class, 'auditMarks']);
            Route::get('/user-groups', [GroupController::class, 'userGroups']);
            Route::get('/statistics/{group_id}/{week_filter?}', [GroupController::class, 'statistics']);
            Route::get('/theses-and-screens-by-week/{group_id}/{filter}', [GroupController::class, 'thesesAndScreensByWeek']);
            Route::get('/month-achievement/{group_id}/{filter}', [GroupController::class, 'monthAchievement']);
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
            Route::post('/add-social-media', [SocialMediaController::class, 'addSocialMedia']);
            Route::get('/show/{user_id}', [SocialMediaController::class, 'show']);
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
        ########Post########
        #updated RESTful routes by asmaa#
        Route::group(['prefix' => 'posts'], function () {
            Route::get('/', [PostController::class, 'index']);
            Route::post('/', [PostController::class, 'create']);
            Route::get('/{id}', [PostController::class, 'show'])->where('id', '[0-9]+');
            Route::put('/{id}', [PostController::class, 'update']);
            Route::delete('/{id}', [PostController::class, 'delete']);
            Route::get('/timeline/{timeline_id}', [PostController::class, 'postsByTimelineId'])->where('timeline_id', '[0-9]+');
            Route::get('/users/{user_id}', [PostController::class, 'postByUserId'])->where('user_id', '[0-9]+');
            Route::get('/pending/timelines/{timeline_id}', [PostController::class, 'listPostsToAccept'])->where('timeline_id', '[0-9]+');
            Route::patch('/accept/{id}', [PostController::class, 'acceptPost'])->where('id', '[0-9]+');
            Route::patch('/decline/{id}', [PostController::class, 'declinePost'])->where('id', '[0-9]+');
            Route::patch('/{id}/control-comments', [PostController::class, 'controlComments']);
            Route::patch('/pin/{id}', [PostController::class, 'pinPost'])->where('id', '[0-9]+');
            Route::get('/home', [PostController::class, 'getPostsForMainPage']);
            Route::get('/announcements', [PostController::class, 'getAnnouncements']);
            Route::get('/support', [PostController::class, 'getSupportPosts']);
            Route::get('/support/latest', [PostController::class, 'getLastSupportPost']);
            Route::get('/pending/timeline/{timeline_id}/{post_id?}', [PostController::class, 'getPendingPosts']);
        });
        ########End Post########

        ########Poll-Vote########
        Route::group(['prefix' => 'poll-votes'], function () {
            Route::get('/', [PollVoteController::class, 'index']);
            Route::post('/', [PollVoteController::class, 'create']);
            Route::post('/show', [PollVoteController::class, 'show']);
            Route::post('/votesByPostId', [PollVoteController::class, 'votesByPostId']);
            Route::post('/votesByUserId', [PollVoteController::class, 'votesByUserId']);
            Route::post('/update', [PollVoteController::class, 'update']);
            Route::post('/delete', [PollVoteController::class, 'delete']);
            Route::get('/posts/{post_id}/users/{user_id?}', [PollVoteController::class, 'getPostVotesUsers'])->where('post_id', '[0-9]+')->where('user_id', '[0-9]+');
        });
        ########End Poll-Vote########
        ########User-Profile########
        Route::group(['prefix' => 'user-profile'], function () {
            Route::get('/show/{user_id}', [UserProfileController::class, 'show']);
            Route::get('/show-to-update', [UserProfileController::class, 'showToUpdate']);
            Route::get('/statistics/{user_id}', [UserProfileController::class, 'profileStatistics']);
            Route::post('/update', [UserProfileController::class, 'update']);
            Route::post('/update-profile-pic', [UserProfileController::class, 'updateProfilePic']);
            Route::post('/update-profile-cover', [UserProfileController::class, 'updateProfileCover']);
            // Route::get('/profile-image/{fileName}/{profileID}', [UserProfileController::class, 'getImages']);


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
            Route::get('/list-all', [NotificationController::class, 'listAllNotification']);
            Route::get('/un-read', [NotificationController::class, 'listUnreadNotification']);
            Route::get('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
            Route::get('/mark-as-read/{notification_id}', [NotificationController::class, 'markAsRead']);
        });
        ######## End Notification ########
        ####### Start UserGroup ########
        Route::group(['prefix' => 'user-group'], function () {

            Route::get('/', [UserGroupController::class, 'index']);
            Route::get('/users/{group_id}', [UserGroupController::class, 'usersByGroupID']);
            Route::post('/', [UserGroupController::class, 'create']);
            Route::post('/show', [UserGroupController::class, 'show']);
            Route::post('/add-member', [UserGroupController::class, 'addMember']);
            Route::post('/assignRole', [UserGroupController::class, 'assign_role']);
            Route::post('/updateRole', [UserGroupController::class, 'update_role']);
            Route::post('/listUserGroup', [UserGroupController::class, 'list_user_group']);
        });
        ######## End UserGroup ########
        ####### Start Thesis ########
        Route::group(['prefix' => 'theses'], function () {
            Route::get('/{thesis_id}', [ThesisController::class, 'show'])->where('thesis_id', '[0-9]+');
            Route::get('/book/{book_id}/user/{user_id?}', [ThesisController::class, 'listBookThesis'])->where('book_id', '[0-9]+')->where('user_id', '[0-9]+');
            Route::get('/book/{book_id}/thesis/{thesis_id}', [ThesisController::class, 'getBookThesis'])->where('book_id', '[0-9]+')->where('thesis_id', '[0-9]+');
            Route::get('/user/{user_id}', [ThesisController::class, 'listUserThesis'])->where('user_id', '[0-9]+');
            Route::get('/week/{week_id}', [ThesisController::class, 'listWeekThesis'])->where('week_id', '[0-9]+');
        });
        ######## End Thesis ########
        ######## Room ########
        Route::group(['prefix' => 'room'], function () {
            Route::post('/create', [RoomController::class, 'create']);
            Route::post('/addUserToRoom', [RoomController::class, 'addUserToRoom']);
        });
        ######## Room ########
        ######## Week ########
        Route::group(['prefix' => 'weeks'], function () {
            Route::post('/', [WeekController::class, 'create']);
            Route::post('/update', [WeekController::class, 'update']);
            Route::get('/', [WeekController::class, 'get_last_weeks_ids']); //for testing - to be deleted
            Route::get('/title', [WeekController::class, 'getDateWeekTitle']);
            Route::post('/insert_week', [WeekController::class, 'insert_week']);
            Route::get('/close-comments', [WeekController::class, 'closeBooksAndSupportComments']);
            Route::get('/open-comments', [WeekController::class, 'openBooksComments']);
            Route::get('/check-date', [WeekController::class, 'testDate']);
            Route::patch('/update-exception/{exp_id}/{status}', [WeekController::class, 'update_exception_status']);
            Route::get('/notify-users', [WeekController::class, 'notifyUsersNewWeek']);
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
        // Route::group(['prefix' => 'book-type'], function () {
        //     Route::get('/', [BookTypeController::class, 'index']);
        //     Route::post('/create', [BookTypeController::class, 'create']);
        //     Route::post('/show', [BookTypeController::class, 'show']);
        //     Route::post('/update', [BookTypeController::class, 'update']);
        //     Route::post('/delete', [BookTypeController::class, 'delete']);
        // });
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