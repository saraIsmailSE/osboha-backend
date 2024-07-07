<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\{
    ActivityController,
    ArticleController,
    Auth\AuthController,
    Auth\EmailVerificationController,
    BookController,
    BookSuggestionController,
    BookStatisticsController,
    PostController,
    PollVoteController,
    RateController,
    ReactionController,
    AmbassadorsRequestsController,
    SystemIssueController,
    CommentController,
    MarkController,
    AuditMarkController,
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
    StatisticsController,
    BookTypeController,
    BookLevelController,
    LanguageController,
    ExceptionTypeController,
    GeneralConversationController,
    GroupTypeController,
    MediaController,
    PostTypeController,
    ThesisTypeController,
    TimelineTypeController,
    WeekController,
    MessagesController,
    ModificationReasonController,
    ModifiedThesesController,
    UserBookController,
    UserController,
    RolesAdministrationController,
    TeamsDischargeController,
    WorkingHourController,
};

use App\Http\Controllers\Api\Eligible\{
    EligibleUserBookController,
    EligibleThesisController,
    EligibleQuestionController,
    EligibleCertificatesController,
    EligibleGeneralInformationsController,
    EligiblePDFController,
    TeamStatisticsController
};

use App\Http\Controllers\Api\Ramadan\{
    RamadanDayController,
    RamadanNightPrayerController,
    RamadanGolenDayController,
    RamadanHadithController,
    RamadanHadithMemorizationController,
    RamadanQuranWirdController,
    RamadanQuestionAnswerController,
    RamadanQuestionController,
};
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Osboha Main API Routes
|--------------------------------------------------------------------------
|
*/


Route::group(['prefix' => 'v1'], function () {

    ########Start Media########
    Route::group(['prefix' => 'media'], function () {
        Route::get('/show/{id}', [MediaController::class, 'show']);
        Route::post('/upload', [MediaController::class, 'upload']);
        Route::delete('/old', [MediaController::class, 'removeOldMedia']);
    });
    ########End Media route########


    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'signUp']);
    Route::post('/new_register', [AuthController::class, 'signUp_v2']);

    Route::get('/profile-image/{profile_id}/{file_name}', [UserProfileController::class, 'getImages'])->where('file_name', '.*');
    Route::get('/official_document/{user_id}', [UserProfileController::class, 'getOfficialDocument'])->where('file_name', '.*');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('password/forgot-password', [AuthController::class, 'sendResetLinkResponse'])->name('passwords.sent');
    Route::post('password/reset', [AuthController::class, 'sendResetResponse'])->name('passwords.reset');

    Route::get('/return-to-team', [AuthController::class, 'returnToTeam'])->middleware('auth:sanctum', 'verified');

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
        Route::post('email/reset', [AuthController::class, 'resetEmail']);

        ########AmbassadorsRequests########
        Route::controller(AmbassadorsRequestsController::class)->prefix('ambassadors-request')->group(function () {
            Route::get('/allocate-ambassador/{leader_gender}/{user_id?}', 'allocateAmbassador');
            Route::get('/check-ambassador/{user_id}', 'checkAmbassador');
        });
        ########End AmbassadorsRequests########

    });


    // generate eligible-certificates
    Route::get('eligible-certificates/generate-pdf/{user_book_id}', [EligiblePDFController::class, 'generatePDF']);

    Route::middleware('auth:sanctum', 'verified', 'IsActiveUser')->group(function () {
        Broadcast::routes();

        Route::post('/refresh', [AuthController::class, 'refresh']);

        Route::get('/get-roles/{id}', [AuthController::class, 'getRoles']);
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/session-data', [AuthController::class, 'sessionData']);

        ########Users########
        Route::group(["prefix" => "users"], function () {
            Route::get('/search', [UserController::class, 'searchUsers'])->where('searchQuery', '.*');
            Route::get('/search-by-email/{email}', [UserController::class, 'searchByEmail']);
            Route::get('/search-by-name/{name}', [UserController::class, 'searchByName']);
            Route::post('/assign-to-parent', [UserController::class, 'assignToParent']);
            Route::get('/info/{id}', [UserController::class, 'getInfo']);
            Route::get('/list-un-allowed-to-eligible', [UserController::class, 'listUnAllowedToEligible']);
            Route::patch('/allow-to-eligible/{id}', [UserController::class, 'acceptEligibleUser']);
            Route::post('/deactive-user', [UserController::class, 'deActiveUser']);
            Route::get('/list-in-charge-of', [UserController::class, 'listInChargeOf']);
            Route::get('/retrieve-nested-users/{parentId}', [UserController::class, 'retrieveNestedUsers']);
        });

        ########Start Roles########
        Route::group(["prefix" => "roles"], function () {
            Route::get('/get-eligible-roles', [RolesAdministrationController::class, 'getEligibleRoles']);
            Route::get('/get-marathon-roles', [RolesAdministrationController::class, 'getMarathonRoles']);
            Route::get('/get-special-care-roles', [RolesAdministrationController::class, 'getSpecialCareRoles']);
            Route::get('/get-ramadan-roles', [RolesAdministrationController::class, 'getRamadanRoles']);
            Route::post('/assign-role-v2', [RolesAdministrationController::class, 'assignRoleV2']);
            Route::post('/assign-role', [RolesAdministrationController::class, 'assignRole']);
            Route::post('/change-advising-team', [RolesAdministrationController::class, 'ChangeAdvisingTeam']);
            Route::post('/supervisors-swap', [RolesAdministrationController::class, 'supervisorsSwap']);
            Route::post('/new-supervisor-current-to-ambassador', [RolesAdministrationController::class, 'newSupervisor_currentToAmbassador']);
            Route::post('/new-supervisor-current-to-leader', [RolesAdministrationController::class, 'newSupervisor_currentToLeader']);
            Route::post('/new-leader-current-to-ambassador', [RolesAdministrationController::class, 'newLeader_currentToAmbassador']);
            Route::post('/transfer-ambassador', [RolesAdministrationController::class, 'transferAmbassador']);
            Route::post('/transfer-leader', [RolesAdministrationController::class, 'transferLeader']);
            Route::post('/remove-secondary-role', [RolesAdministrationController::class, 'removeSecondaryRole']);
        });
        ########End Roles########


        ########Book########
        Route::group(['prefix' => 'books'], function () {
            Route::get('/', [BookController::class, 'index']);
            Route::post('/', [BookController::class, 'create']);
            Route::get('/{id}', [BookController::class, 'show'])->where('id', '[0-9]+');
            Route::post('/update', [BookController::class, 'update']);
            Route::delete('/{id}', [BookController::class, 'delete']);
            Route::get('/type/{type_id}', [BookController::class, 'bookByType'])->where('type_id', '[0-9]+');
            Route::get('/level/{level}', [BookController::class, 'bookByLevel']);
            Route::get('/section/{section_id}', [BookController::class, 'bookBySection'])->where('section_id', '[0-9]+');
            Route::get('/name', [BookController::class, 'bookByName']);
            Route::get('/language/{language}', [BookController::class, 'bookByLanguage']);
            Route::get('/recent-added-books', [BookController::class, 'getRecentAddedBooks']);
            Route::get('/most-readable-books', [BookController::class, 'getMostReadableBooks']);
            Route::get('/random-book', [BookController::class, 'getRandomBook']);
            Route::get('/latest', [BookController::class, 'latest']);
            Route::get('/eligible', [BookController::class, 'getAllForEligible']);
            Route::get('/ramadan', [BookController::class, 'getAllForRamadan']);
            Route::post('/report', [BookController::class, 'createReport']);
            Route::get('/reports/{status}', [BookController::class, 'listReportsByStatus']);
            Route::post('/update-report', [BookController::class, 'updateReportStatus']);
            Route::get('/report/{id}', [BookController::class, 'showReport']);
            Route::get('/book/{book_id}/reports', [BookController::class, 'listReportsForBook']);
            Route::get('/remove-book-from-osboha/{book_id}', [BookController::class, 'removeBookFromOsboha']);
            Route::get('/is-book-exist/{searchTerm}', [BookController::class, 'isBookExist']);
        });
        ########End Book########
        ########User Book########
        Route::group(['prefix' => 'user-books'], function () {
            Route::get('/show/{user_id}', [UserBookController::class, 'show']);
            Route::get('/later-books/{user_id}', [UserBookController::class, 'later']);
            Route::get('/free-books/{user_id}/', [UserBookController::class, 'free']);
            Route::get('/delete-for-later-book/{id}', [UserBookController::class, 'deleteForLater']);
            Route::post('/update', [UserBookController::class, 'update']);
            Route::delete('/{id}', [UserBookController::class, 'delete']);
            Route::patch('{id}/save-for-later/', [UserBookController::class, 'saveBookForLater']);
            Route::get('/eligible-to-write-thesis/{user_id}', [UserBookController::class, 'eligibleToWriteThesis']);
            Route::get('/book_quality_users_statics/{week_id?}', [UserBookController::class, 'bookQualityUsersStatics']);
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

        ########AmbassadorsRequests########
        Route::controller(AmbassadorsRequestsController::class)->prefix('ambassadors-request')->group(function () {
            Route::post('/create', 'create');
            Route::post('/update', 'update');
            Route::get('/show/{id}', 'show')->where('id', '[0-9]+');
            Route::get('/statistics/{timeFrame}', 'statistics');
            Route::delete('/delete/{id}', 'delete')->where('id', '[0-9]+');
            Route::get('/latest-group-request/{group_id}', 'latest')->where('group_id', '[0-9]+');
            Route::get('/list-requests/{retrieveType}/{is_done}/{name?}', 'listRequests');
        });
        ########End AmbassadorsRequests########

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
        // ########Start Media########
        // Route::group(['prefix' => 'media'], function () {
        //     Route::get('/show/{id}', [MediaController::class, 'show']);
        // });
        // ########End Media route########
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
            Route::get('/accept-all', [FriendController::class, 'acceptAll']);
            Route::get('/delete-all-unaccepted', [FriendController::class, 'deleteAllUnAccepted']);
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
            Route::get('/ambassador-mark/{user_id}/{week_id}', [MarkController::class, 'ambassadorMark']);
            Route::get('/marathon-ambassador-mark/{user_id}/{week_id}', [MarkController::class, 'marathonAmbassadorMark']);
            Route::put('/accept-support/user/{user_id}/{week_id}', [MarkController::class, 'acceptSupport']);
            Route::put('/add-one-thesis-mark/user/{user_id}/{week_id}', [MarkController::class, 'addOneThesisMark']);
            Route::put('/reject-support/user/{user_id}/{week_id}', [MarkController::class, 'rejectSupport']);
            Route::post('/set-support-for-all', [MarkController::class, 'setSupportMarkForAll']);
            Route::get('/top-users-by-month', [MarkController::class, 'topUsersByMonth']);
            Route::get('/top-users-by-week', [MarkController::class, 'topUsersByWeek']);
            Route::put('/set-activity-mark/{user_id}/{week_id}', [MarkController::class, 'setActivityMark']);
            Route::put('/unset-activity-mark/{user_id}/{week_id}', [MarkController::class, 'unsetActivityMark']);
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
            Route::get('/advisor-main-audit/{advisor_id}', [AuditMarkController::class, 'advisorMainAudit']);
            Route::post('/add-note', [AuditMarkController::class, 'addNote']);
            Route::get('/get-notes/{mark_for_audit_id}', [AuditMarkController::class, 'getNotes']);
            Route::get('/pending-theses/{supervisor_id}/{week_id?}', [AuditMarkController::class, 'pendingTheses']);
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
            Route::post('/set-exceptional-freez', [UserExceptionController::class, 'setExceptionalFreez']);
            Route::post('/set-new-user', [UserExceptionController::class, 'setNewUser']);
            Route::get('/search-by-email/{email}', [UserExceptionController::class, 'searchByEmail']);
            Route::get('/list-by-advisor/{exception_type}/{advisor_id}', [UserExceptionController::class, 'listForAdvisor']);
        });
        ############End UserException########

        ############ Start Group ############
        Route::group(['prefix' => 'group'], function () {
            Route::get('/list-all/{retrieveType}/{name?}', [GroupController::class, 'listGroups']);
            Route::get('/search-group-by-name/{name}', [GroupController::class, 'searchGroupByName']);
            Route::post('/create', [GroupController::class, 'create']);
            Route::get('/show/{group_id}', [GroupController::class, 'show']);
            Route::get('/show-basic-info/{group_id}', [GroupController::class, 'showBasicInfo']);
            Route::get('/group-by-type/{type}', [GroupController::class, 'GroupByType']);
            Route::post('/update', [GroupController::class, 'update']);
            Route::delete('/delete/{group_id}', [GroupController::class, 'delete']);
            Route::get('/books/{group_id}', [GroupController::class, 'books']);
            Route::get('/group-exceptions/{group_id}', [GroupController::class, 'groupExceptions']);
            Route::get('/exceptions-filter/{filter}/{group_id}', [GroupController::class, 'exceptionsFilter']);
            Route::get('/basic-mark-view/{group_id}/{week_id}', [GroupController::class, 'BasicMarksView']);
            Route::get('/marathon-reading/{group_id}/{week_id}', [GroupController::class, 'MarathonReading']);
            Route::get('/all-achievements/{group_id}/{week_id}', [GroupController::class, 'allAchievements']);
            Route::get('/search-for-ambassador-achievement/{ambassador_name}/{group_id}/{week_filter?}', [GroupController::class, 'searchForAmbassadorAchievement']);
            Route::get('/search-for-ambassador/{ambassador_name}/{group_id}', [GroupController::class, 'searchForAmbassador']);
            Route::get('/achievement-as-pages/{group_id}/{week_id}', [GroupController::class, 'achievementAsPages']);
            Route::post('/create-leader-request', [GroupController::class, 'createLeaderRequest']);
            Route::get('/last-leader-request/{group_id}', [GroupController::class, 'lastLeaderRequest']);
            Route::get('/audit-marks/{group_id}', [GroupController::class, 'auditMarks']);
            Route::get('/user-groups', [GroupController::class, 'userGroups']);
            Route::get('/statistics/{group_id}/{week_id}', [GroupController::class, 'statistics']);
            Route::get('/theses-and-screens-by-week/{group_id}/{filter}', [GroupController::class, 'thesesAndScreensByWeek']);
            Route::get('/month-achievement/{group_id}/{filter}', [GroupController::class, 'monthAchievement']);
            Route::post('/assign-administrator', [GroupController::class, 'assignAdministrator']);
            Route::post('/assign-supervisor', [GroupController::class, 'assignSupervisor']);
            Route::get('/list-marathon-participants', [GroupController::class, 'getMarathonParticipants']);
            Route::get('/current-ambassadors-count/{id}', [GroupController::class, 'currentAmbassadorsCount']);
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
            Route::get('/friday-thesis', [PostController::class, 'getFridayThesisPosts']);
            Route::get('/friday-thesis/latest', [PostController::class, 'getLastFridayThesisPost']);
            Route::get('/pending/timeline/{timeline_id}/{post_id?}', [PostController::class, 'getPendingPosts']);
            Route::get('/current-week-support', [PostController::class, 'getCurrentWeekSupportPost']);
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
            Route::post('/update-official-document', [UserProfileController::class, 'updateOfficialDocument']);
        });
        ########End User-Profile########

        ######## Statistics ########
        Route::group(['prefix' => 'statistics'], function () {
            Route::get('/by-week/{week_id?}', [StatisticsController::class, 'byWeek']);
            Route::get('/last-week', [StatisticsController::class, 'lastWeek']);
            Route::get('/leaders-statistics/{superviser_id}/{week_filter?}', [StatisticsController::class, 'supervisingStatistics']);
            Route::get('/supervisors-statistics/{advisor_id}/{week_filter?}', [StatisticsController::class, 'advisorsStatistics']);
            Route::get('/advisors-statistics/{consultant_id}/{week_filter?}', [StatisticsController::class, 'consultantsStatistics']);
            Route::get('/consultant-statistics/{admin_id}/{week_filter?}', [StatisticsController::class, 'administratorStatistics']);
        });
        ######## End Statisticx########

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
            Route::delete('/delete/{user_group_id}', [UserGroupController::class, 'delete']);
            Route::post('/withdrawn', [UserGroupController::class, 'withdrawnMember']);
            Route::get('/members-by-month/{group_id}/{month_filter}', [UserGroupController::class, 'membersByMonth']);
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
        Route::group(['prefix' => 'book-type'], function () {
            Route::get('/', [BookTypeController::class, 'index']);
            Route::post('/create', [BookTypeController::class, 'create']);
            Route::post('/show', [BookTypeController::class, 'show']);
            Route::post('/update', [BookTypeController::class, 'update']);
            Route::post('/delete', [BookTypeController::class, 'delete']);
        });
        ######## Book-Type ########

        ######## Book-Level ########
        Route::group(['prefix' => 'book-level'], function () {
            Route::get('/', [BookLevelController::class, 'index']);
            Route::post('/create', [BookLevelController::class, 'create']);
            Route::post('/show', [BookLevelController::class, 'show']);
            Route::post('/update', [BookLevelController::class, 'update']);
            Route::post('/delete', [BookLevelController::class, 'delete']);
        });
        ######## Book-Type ########

        ######## Book-Language ########
        Route::group(['prefix' => 'language'], function () {
            Route::get('/', [LanguageController::class, 'index']);
            Route::post('/create', [LanguageController::class, 'create']);
            Route::post('/show', [LanguageController::class, 'show']);
            Route::post('/update', [LanguageController::class, 'update']);
        });
        ######## Book-Language ########

        ######## Book-Suggestion ########
        Route::controller(BookSuggestionController::class)->prefix('book-suggestion')->group(function () {
            Route::post('/create', 'create');
            Route::post('/update-status', 'updateStatus');
            Route::get('/show/{suggestion_id}',  'show');
            Route::get('/list-by-status/{status}',  'listByStatus');
            Route::get('/is-allowed-to-suggest',  'isAllowedToSuggest');
        });
        ######## End Book-Suggestion ########

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

        ######## Room ########
        Route::group(['prefix' => 'rooms'], function () {
            Route::post('/create', [RoomController::class, 'create']);
            Route::post('/addUserToRoom', [RoomController::class, 'addUserToRoom']);
            Route::get("/", [RoomController::class, "listRooms"]);
        });
        ######## Room ########

        ######## Messages ########
        Route::prefix('messages')->group(function () {
            Route::post('/', [MessagesController::class, 'create']);
            Route::post("/room/{room_id}", [MessagesController::class, "setMessagesAsRead"]);
            Route::get('/room/{room_id}', [MessagesController::class, 'listRoomMessages']);
            Route::delete("/{message_id}", [MessagesController::class, "deleteMessage"]);
            Route::get("/unread-messages", [MessagesController::class, "unreadMessages"]);
        });
        ######## Messages ########
        ######## BookStatistics ########
        Route::group(['prefix' => 'book-stat'], function () {
            Route::get('/', [BookStatisticsController::class, 'index']);
        });
        ######## BookStatistics ########

        ######## WorkingHour ########
        Route::group(['prefix' => 'working-hours'], function () {
            Route::post('/', [WorkingHourController::class, 'addWorkingHours']);
            Route::get('/', [WorkingHourController::class, 'getWorkingHours']);
            Route::get('/statistics', [WorkingHourController::class, 'getWorkingHoursStatistics']);
        });
        ######## End WorkingHour ########

        ######## GeneralConversation ########
        Route::group(['prefix' => 'general-conversations'], function () {
            Route::group(['prefix' => 'questions'], function () {
                Route::get('/index', [GeneralConversationController::class, 'index']);
                Route::post('/', [GeneralConversationController::class, 'addQuestion']);
                Route::get('/', [GeneralConversationController::class, 'getAllQuestions']);
                Route::get('/{question_id}', [GeneralConversationController::class, 'getQuestionById'])->where('question_id', '[0-9]+');
                Route::get('/{question_id}/check-late', [GeneralConversationController::class, 'checkQuestionLate']);
                Route::put('/{question_id}/close', [GeneralConversationController::class, 'closeQuestion']);
                Route::put('/{question_id}/solve', [GeneralConversationController::class, 'solveQuestion']);
                Route::put('/{question_id}/assign-to-parent', [GeneralConversationController::class, 'AssignQuestionToParent']);
                Route::put('/{question_id}/move-to-discussion', [GeneralConversationController::class, 'moveQuestionToDiscussion']);
                Route::put('/{question_id}/move-to-questions', [GeneralConversationController::class, 'moveQuestionToQuestions']);
                Route::get('/my-questions', [GeneralConversationController::class, 'getMyQuestions']);
                Route::get('/my-active-questions', [GeneralConversationController::class, 'getMyActiveQuestions']);
                Route::get('/my-late-questions', [GeneralConversationController::class, 'getMyLateQuestions']);
                Route::get('/my-assigned-to-parent-questions', [GeneralConversationController::class, 'getMyAssignedToParentQuestions']);
                Route::get('/discussion-questions', [GeneralConversationController::class, 'getDiscussionQuestions']);
                Route::get('/statistics', [GeneralConversationController::class, 'getQuestionsStatistics']);
            });

            Route::group(['prefix' => 'answers'], function () {
                Route::post('/', [GeneralConversationController::class, 'answerQuestion']);
            });
        });
        ######## End GeneralConversation ########

        /*
|--------------------------------------------------------------------------|
|                       Eligible API Routes                                |
|--------------------------------------------------------------------------|
*/
        //user book routes
        Route::group(['prefix' => 'eligible-userbook'], function () {
            Route::get('/', [EligibleUserBookController::class, 'index']);
            Route::post('/', [EligibleUserBookController::class, 'store']);
            Route::get('/status/{status}', [EligibleUserBookController::class, 'getUserBookByStatus']);
            Route::patch('/status/{id}', [EligibleUserBookController::class, 'changeStatus']);
            Route::get('/last-achievement', [EligibleUserBookController::class, "lastAchievement"]);
            Route::get('/finished-achievement', [EligibleUserBookController::class, "finishedAchievement"]);
            Route::get('/count', [EligibleUserBookController::class, "checkOpenBook"]);
            Route::get('/certificate/{id}', [EligibleUserBookController::class, "checkCertificate"]);
            Route::get('/statistics/{id}', [EligibleUserBookController::class, "getStatistics"]);
            Route::get('/general-statistics/', [EligibleUserBookController::class, "getGeneralstatistics"]);
            Route::get('/by-book-id/{bookId}', [EligibleUserBookController::class, "getByBookID"]);
            Route::get('/stage-status/{id}', [EligibleUserBookController::class, "getStageStatus"]);
            Route::get('/{id}', [EligibleUserBookController::class, 'show']);
            Route::patch('/{id}', [EligibleUserBookController::class, 'update']);
            Route::delete('/{id}', [EligibleUserBookController::class, 'destroy']);
            Route::post('/review', [EligibleUserBookController::class, "review"]);
            Route::get('/ready/to', [EligibleUserBookController::class, "readyToAudit"]);
            Route::get('/check-achievement/{id}', [EligibleUserBookController::class, 'checkAchievement']);
        });

        //thesis routes
        Route::group(['prefix' => 'eligible-theses'], function () {
            Route::get('/image', [EligibleThesisController::class, 'image']);
            Route::get('/', [EligibleThesisController::class, 'index']);
            Route::post('/', [EligibleThesisController::class, 'store']);
            Route::get('final-degree/{id}', [EligibleThesisController::class, "finalDegree"]);
            Route::get('by-status/{status}', [EligibleThesisController::class, "getByStatus"]);
            Route::get('/photo-count/{id}', [EligibleThesisController::class, 'getThesisPhotosCount']);
            Route::get('/{id}', [EligibleThesisController::class, 'show']);
            Route::patch('update-photo/{id}', [EligibleThesisController::class, "updatePhoto"]);
            Route::patch('review-thesis/{id}', [EligibleThesisController::class, "reviewThesis"]);
            Route::patch('/{id}', [EligibleThesisController::class, 'update']);
            Route::delete('/photo/{id}', [EligibleThesisController::class, 'deletePhoto']);
            Route::delete('/{id}', [EligibleThesisController::class, 'destroy']);
            Route::patch('add-degree/{id}', [EligibleThesisController::class, "addDegree"]);

            Route::post('update-photo', [EligibleThesisController::class, "updatePicture"]);
            Route::post('upload/{id}', [EligibleThesisController::class, "uploadPhoto"]);
            Route::get('eligible_user_books_id/{user_book_id}&{status?}', [EligibleThesisController::class, "getByUserBook"]);
            Route::get('book/{book_id}', [EligibleThesisController::class, "getByBook"]);
            Route::post('/review', [EligibleThesisController::class, "review"]);
        });

        //questions routes
        Route::group(['prefix' => 'eligible-questions'], function () {
            Route::get('/', [EligibleQuestionController::class, 'index']);
            Route::post('/', [EligibleQuestionController::class, 'store']);
            Route::get('status/{status}', [EligibleQuestionController::class, "getByStatus"]);
            Route::get('user-book/{id}', [EligibleQuestionController::class, "getUserBookQuestions"]);
            Route::get('/{id}', [EligibleQuestionController::class, 'show']);
            Route::patch('/{id}', [EligibleQuestionController::class, 'update']);
            Route::delete('/{id}', [EligibleQuestionController::class, 'destroy']);
            Route::patch('add-degree/{id}', [EligibleQuestionController::class, "addDegree"]);
            Route::get('book/{book_id}', [EligibleQuestionController::class, "getByBook"]);
            Route::get('final-degree/{id}', [EligibleQuestionController::class, "finalDegree"]);
            Route::get('user_book_id/{user_book_id}', [EligibleQuestionController::class, "getByUserBook"]);
            Route::get('status/{status}', [EligibleQuestionController::class, "getByStatus"]);
            Route::post('/review', [EligibleQuestionController::class, "review"]);
            Route::patch('review-question/{id}', [EligibleQuestionController::class, "reviewQuestion"]);
        });

        //certificates routes
        Route::group(['prefix' => 'eligible-certificates'], function () {
            Route::get('/', [EligibleCertificatesController::class, 'index']);
            Route::post('/', [EligibleCertificatesController::class, 'store']);
            Route::get('/user', [EligibleCertificatesController::class, 'getUserCertificates']);
            Route::get('/{id}', [EligibleCertificatesController::class, 'show']);
            Route::get('/full-certificate/{user_book_id}', [EligibleCertificatesController::class, 'fullCertificate']);
            Route::patch('/{id}', [EligibleCertificatesController::class, 'update']);
            Route::delete('/{id}', [EligibleCertificatesController::class, 'destroy']);
        });


        //general informations routes
        Route::group(['prefix' => 'eligible-general-informations'], function () {
            Route::get('/', [EligibleGeneralInformationsController::class, 'index']);
            Route::post('/', [EligibleGeneralInformationsController::class, 'store']);
            Route::get('/user_book_id/{user_book_id}', [EligibleGeneralInformationsController::class, 'getByUserBookId']);
            Route::get('/{id}', [EligibleGeneralInformationsController::class, 'show']);
            Route::patch('/{id}', [EligibleGeneralInformationsController::class, 'update']);
            Route::delete('/{id}', [EligibleGeneralInformationsController::class, 'destroy']);
            Route::patch('add-degree/{id}', [EligibleGeneralInformationsController::class, "addDegree"]);
            Route::get('book/{book_id}', [EligibleGeneralInformationsController::class, "getByBook"]);
            Route::get('final-degree/{id}', [EligibleGeneralInformationsController::class, "finalDegree"]);
            Route::get('user_book_id/{user_book_id}', [EligibleGeneralInformationsController::class, "getByUserBook"]);
            Route::get('status/{status}', [EligibleGeneralInformationsController::class, "getByStatus"]);
            Route::post('/review', [EligibleGeneralInformationsController::class, "review"]);
            Route::patch('review-general-informations/{id}', [EligibleGeneralInformationsController::class, "reviewGeneralInformations"]);
        });

        //Eligible Statistics
        Route::group(['prefix' => 'eligible-statistics'], function () {
            Route::get('/my-team/{week_id}', [TeamStatisticsController::class, 'teamStatistics']);
        });

        ######## Emptying ########
        Route::controller(TeamsDischargeController::class)->prefix('teams-discharge')->group(function () {
            // Route::post('/all/members', 'allMembersForEmptyingGroup');
            // Route::post('/move/ambassadors', 'moveGroupOfAmbassadors');
            // Route::post('/move/advisors', 'moveGroupOfAdvisors');
            // Route::post('/move/advisors', 'moveGroupOfSupervisors');
            // Route::post('/group', 'EmptyingGroup');

            Route::post('/discharge', 'discharge');
        });
        ######## Emptying ########

        /*
    |--------------------------------------------------------------------------|
    |                       Ramadan API Routes                                |
    |--------------------------------------------------------------------------|
    */
        Route::group(['prefix' => 'ramadan-day'], function () {
            Route::get('/all', [RamadanDayController::class, 'all']);
            Route::get('/current', [RamadanDayController::class, 'currentDay']);
            Route::get('/previous', [RamadanDayController::class, 'previousDay']);
            Route::get('/day-by-id/{id}', [RamadanDayController::class, 'dayById']);
        });

        Route::group(['prefix' => 'ramadan-golden-day'], function () {
            Route::post('/store', [RamadanGolenDayController::class, 'store']);
            Route::get('/statistics/{ramadan_day_id}', [RamadanGolenDayController::class, 'statistics']);
            Route::get('/show/{ramadan_day_id}', [RamadanGolenDayController::class, 'show']);
        });


        Route::group(['prefix' => 'ramadan-night-pray'], function () {
            Route::post('/store', [RamadanNightPrayerController::class, 'store']);
            Route::get('/statistics/{ramadan_day_id}', [RamadanNightPrayerController::class, 'statistics']);
            Route::get('/show/{ramadan_day_id}', [RamadanNightPrayerController::class, 'show']);
        });

        Route::prefix('ramadan-hadith-memorization')->group(function () {
            Route::post('/', [RamadanHadithMemorizationController::class, 'create']);
            Route::get('/show/{hadithMemorizationId}', [RamadanHadithMemorizationController::class, 'show'])->where('hadithMemorizationId', '[0-9]+');
            Route::post('/correct', [RamadanHadithMemorizationController::class, 'correct']);
            Route::get('/statistics/{ramadan_day_id}', [RamadanHadithMemorizationController::class, 'statistics']);
            Route::get('/pending', [RamadanHadithMemorizationController::class, 'getMemorizedHadiths']);
        });

        Route::prefix('ramadan-hadith')->group(function () {
            Route::get('/', [RamadanHadithController::class, 'index']);
            Route::get('/days/{day_id}', [RamadanHadithController::class, 'getHadithByDay'])->where('day_id', '[0-9]+');
            Route::get('/show/{id}', [RamadanHadithController::class, 'show'])->where('day_id', '[0-9]+');
        });
        Route::prefix('ramadan-quran-wird')->group(function () {
            Route::post('/store', [RamadanQuranWirdController::class, 'store']);
            Route::get('/show/{ramadan_day_id}', [RamadanQuranWirdController::class, 'show']);
            Route::get('/statistics/{ramadan_day_id}', [RamadanQuranWirdController::class, 'statistics']);
        });

        Route::controller(RamadanQuestionAnswerController::class)->prefix('ramadan-question-answer')->group(function () {
            Route::post('/store', 'store');
            Route::post('/correct', 'correctAnswer');
            Route::get('/show/{id}', 'show');
            Route::get('/get-pending-questions/{category}', 'getPending');
        });
        Route::controller(RamadanQuestionController::class)->prefix('ramadan-question')->group(function () {
            Route::get('/day/{day_id}', 'getQuestionsByDay')->where('day_id', '[0-9]+');
            Route::get('/show/{id}', 'show')->where('day_id', '[0-9]+');
        });
    });
});
