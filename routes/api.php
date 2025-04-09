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
    ContactsWithWithdrawnController,
    PostController,
    PollVoteController,
    RateController,
    ReactionController,
    AmbassadorsRequestsController,
    CommentController,
    MarkController,
    AuditMarkController,
    UserExceptionController,
    GroupController,
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
    MarkNoteController,
};

use App\Http\Controllers\Api\Eligible\{
    EligiblePDFController,
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
    Route::controller(MediaController::class)
        ->prefix('media')
        ->group(function () {
            Route::get('/show/{id}', 'show');
            Route::post('/upload', 'upload');
            Route::delete('/old', 'removeOldMedia');
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

        ######## USER ########
        Route::controller(UserController::class)->prefix('users')->group(function () {
            Route::get('/show/{user_id}', 'show');
            Route::post('/update-info', 'updateInfo');
        });
        ######## End USER ########
        ########Start SocialMedia########
        Route::group(['prefix' => 'socialMedia'], function () {
            Route::get('/show/{user_id}', [SocialMediaController::class, 'show']);
        });
        ########End SocialMedia########


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
        Route::controller(UserController::class)
            ->prefix('users')
            ->group(function () {
                Route::get('/search', 'searchUsers')->where('searchQuery', '.*');
                Route::get('/search-by-email/{email}', 'searchByEmail');
                Route::get('/in-charge-of-search/{email}', 'inChargeOfSearch');
                Route::get('/search-by-name/{name}', 'searchByName');
                Route::post('/assign-to-parent', 'assignToParent');
                Route::get('/info/{id}', 'getInfo');
                Route::get('/list-un-allowed-to-eligible', 'listUnAllowedToEligible');
                Route::patch('/allow-to-eligible/{id}', 'acceptEligibleUser');
                Route::post('/deactive-user', 'deActiveUser');
                Route::get('/list-in-charge-of', 'listInChargeOf');
                Route::get('/retrieve-nested-users/{parentId}', 'retrieveNestedUsers');
                Route::post('/get_ambassador_marks_four_week/{email}', 'getAmbassadorMarksFourWeek');
                Route::get('/get-users-on-hold/{contact_status}/{month}/{year}/{gender}', 'getUsersOnHoldByMonthAndGender');
                Route::post('/update-user-name', 'updateUserName');
                Route::get('/withdrawn-ambassador-details/{user_id}', 'withdrawnAmbassadorDetails');
            });

        ########Start Roles########
        Route::controller(RolesAdministrationController::class)
            ->prefix('roles')
            ->group(function () {
                Route::get('/get-secondary-roles/{type}', 'getSecondaryRoles');
                Route::post('/assign-non-basic-role', 'assignNonBasicRole');
                Route::post('/assign-role', 'assignRole')->name('roles.assign');
                Route::post('/change-advising-team', 'ChangeAdvisingTeam');
                Route::post('/supervisors-swap', 'supervisorsSwap');
                Route::post('/new-supervisor-current-to-ambassador', 'newSupervisor_currentToAmbassador');
                Route::post('/new-supervisor-current-to-leader', 'newSupervisor_currentToLeader');
                Route::post('/new-leader-current-to-ambassador', 'newLeader_currentToAmbassador');
                Route::post('/transfer-ambassador', 'transferAmbassador');
                Route::post('/transfer-leader', 'transferLeader');
                Route::post('/remove-secondary-role', 'removeSecondaryRole');
                Route::get('/secondary-roles-by-role', 'getSecondaryRolesByRole');
            });
        ########End Roles########

        ########Book########
        Route::controller(BookController::class)
            ->prefix('books')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'create');
                Route::get('/{id}', 'show')->where('id', '[0-9]+');
                Route::post('/update', 'update');
                Route::delete('/{id}', 'delete');
                Route::get('/type/{type_id}', 'bookByType')->where('type_id', '[0-9]+');
                Route::get('/level/{level}', 'bookByLevel');
                Route::get('/section/{section_id}', 'bookBySection')->where('section_id', '[0-9]+');
                Route::get('/name', 'bookByName');
                Route::get('/language/{language}', 'bookByLanguage');
                Route::get('/recent-added-books', 'getRecentAddedBooks');
                Route::get('/most-readable-books', 'getMostReadableBooks');
                Route::get('/random-book', 'getRandomBook');
                Route::get('/latest', 'latest');
                Route::get('/eligible', 'getAllForEligible');
                Route::get('/ramadan', 'getAllForRamadan');
                Route::post('/report', 'createReport');
                Route::get('/reports/{status}', 'listReportsByStatus');
                Route::post('/update-report', 'updateReportStatus');
                Route::get('/report/{id}', 'showReport');
                Route::get('/book/{book_id}/reports', 'listReportsForBook');
                Route::get('/remove-book-from-osboha/{book_id}', 'removeBookFromOsboha');
                Route::get('/is-book-exist/{searchTerm}', 'isBookExist');
            });
        ########End Book########
        ########User Book########
        Route::controller(UserBookController::class)
            ->prefix('user-books')
            ->group(function () {
                Route::get('/show/{user_id}', 'show');
                Route::get('/later-books/{user_id}', 'later');
                Route::get('/free-books/{user_id}', 'free');
                Route::get('/osboha-user-books/{user_id}/{name?}', 'osbohaUserBook');
                Route::get('/delete-for-later-book/{id}', 'deleteForLater');
                Route::post('/update', 'update');
                Route::delete('/{id}', 'delete');
                Route::patch('{id}/save-for-later', 'saveBookForLater');
                Route::get('/eligible-to-write-thesis/{user_id}', 'eligibleToWriteThesis');
                Route::get('/book_quality_users_statics/{week_id?}', 'bookQualityUsersStatics');
                Route::post('/mark-as-finished', 'markBookAsFinished');
            });
        ########End User Book########
        ########Start Rate########
        Route::controller(RateController::class)
            ->prefix('rates')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'create');
                Route::get('/{rate_id}', 'show');
                Route::post('/{rate_id}', 'update');
                Route::delete('/{rate_id}', 'delete');
                Route::get('/book/{book_id}', 'getBookRates')->where('book_id', '[0-9]+');
            });
        ########End Rate########
        ########Reaction########
        Route::controller(ReactionController::class)
            ->prefix('reactions')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
                Route::get('/types', 'getReactionTypes');
                Route::get('/posts/{post_id}/types/{type_id}', 'reactOnPost')
                    ->where('post_id', '[0-9]+')
                    ->where('type_id', '[0-9]+');
                Route::get('/comments/{comment_id}/types/{type_id}', 'reactOnComment')
                    ->where('comment_id', '[0-9]+')
                    ->where('type_id', '[0-9]+');
                Route::get('/posts/{post_id}/users/{user_id?}', 'getPostReactionsUsers')
                    ->where('post_id', '[0-9]+')
                    ->where('user_id', '[0-9]+');
            });
        ########End Reaction########

        ########AmbassadorsRequests########
        Route::controller(AmbassadorsRequestsController::class)
            ->prefix('ambassadors-request')
            ->group(function () {
                Route::post('/create', 'create');
                Route::post('/update', 'update');
                Route::get('/show/{id}', 'show')->where('id', '[0-9]+');
                Route::get('/statistics/{timeFrame}', 'statistics');
                Route::delete('/delete/{id}', 'delete')->where('id', '[0-9]+');
                Route::get('/latest-group-request/{group_id}', 'latest')->where('group_id', '[0-9]+');
                Route::get('/list-requests/{retrieveType}/{is_done}/{name?}', 'listRequests');
            });
        ########End AmbassadorsRequests########

        ########Start Comment########
        Route::controller(CommentController::class)
            ->prefix('comments')
            ->group(function () {
                Route::post('/', 'create');
                Route::get('/post/{post_id}/{user_id?}', 'getPostComments')
                    ->where('post_id', '[0-9]+')
                    ->where('user_id', '[0-9]+');
                Route::post('/update', 'update'); // for testing
                Route::put('/', 'update'); // gives errors from axios
                Route::delete('/{id}', 'delete');
                Route::get('/post/{post_id}/users', 'getPostCommentsUsers')->where('post_id', '[0-9]+');
            });
        ########End Comment########

        ########Start Friend route########
        Route::controller(FriendController::class)
            ->prefix('friends')
            ->group(function () {
                Route::get('/user/{user_id}', 'listByUserId')->where('user_id', '[0-9]+');
                Route::get('/accepted/{user_id}', 'listByUserId')->where('user_id', '[0-9]+');
                Route::get('/un-accepted', 'listUnAccepted');
                Route::post('/create', 'create');
                Route::get('/{friendship_id}', 'show')->where('friendship_id', '[0-9]+');
                Route::patch('/accept-friend-request/{friendship_id}', 'accept')->where('friendship_id', '[0-9]+');
                Route::delete('/{friendship_id}', 'delete')->where('friendship_id', '[0-9]+');
                Route::get('/accept/{friendship_id}', 'accept')->where('friendship_id', '[0-9]+');
                Route::get('/accept-all', 'acceptAll');
                Route::get('/delete-all-unaccepted', 'deleteAllUnAccepted');
                Route::post('/delete', 'delete');
                Route::get('/show/{friendship_id}', 'show')->where('friendship_id', '[0-9]+');
            });
        ########End Friend route########
        ########Mark########
        Route::controller(MarkController::class)
            ->prefix('marks')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/update', 'update');
                Route::post('/list', 'list_user_mark');
                Route::get('/audit/leaders', 'leadersAuditmarks');
                Route::post('/audit/show', 'showAuditmarks');
                Route::post('/audit/update', 'updateAuditMark');
                // Route::get('/statsmark', 'statsMark'); // معطلة حاليًا
                Route::get('/user-month-achievement/{user_id}/{filter}', 'userMonthAchievement');
                Route::get('/user-week-achievement/{user_id}/{filter}', 'userWeekAchievement');
                Route::get('/ambassador-mark/{user_id}/{week_id}', 'ambassadorMark');
                Route::get('/marathon-ambassador-mark/{user_id}/{week_id}', 'marathonAmbassadorMark');
                Route::put('/accept-support/user/{user_id}/{week_id}', 'acceptSupport');
                Route::put('/add-one-thesis-mark/user/{user_id}/{week_id}', 'addOneThesisMark');
                Route::put('/reject-support/user/{user_id}/{week_id}', 'rejectSupport');
                Route::post('/set-support-for-all', 'setSupportMarkForAll');
                Route::get('/top-users-by-month', 'topUsersByMonth');
                Route::get('/top-users-by-week', 'topUsersByWeek');
                Route::put('/set-activity-mark/{user_id}/{week_id}', 'setActivityMark');
                Route::put('/unset-activity-mark/{user_id}/{week_id}', 'unsetActivityMark');
                Route::get('/quality_team_achievements/{week_id}', 'bookQualityTeamAchievements');
            });
        ########End Mark########

        ######## Start Audit Mark ########
        Route::controller(AuditMarkController::class)
            ->prefix('audit-marks')
            ->group(function () {
                Route::get('/generate', 'generateAuditMarks');
                Route::get('/mark-for-audit/{mark_for_audit_id}', 'markForAudit');
                Route::get('/group-audit-marks/{group_id}', 'groupAuditMarks');
                Route::patch('/update-mark-for-audit-status/{id}', 'updateMarkForAuditStatus');
                Route::get('/groups-audit/{supervisor_id}', 'groupsAudit');
                Route::get('/supervisors-audit/{advisor_id}', 'allSupervisorsForAdvisor');
                Route::get('/advisor-main-audit/{advisor_id}', 'advisorMainAudit');
                Route::post('/add-note', 'addNote');
                Route::get('/get-notes/{mark_for_audit_id}', 'getNotes');
                Route::get('/pending-theses/{supervisor_id}/{week_id?}', 'pendingTheses');
            });
        ######## End Audit Mark ########
        ########Modified Theses########
        Route::controller(ModifiedThesesController::class)
            ->prefix('modified-theses')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'create');
                Route::get('/{id}', 'show')->where('id', '[0-9]+');
                Route::put('/', 'update');
                Route::get('/user/{user_id}', 'listUserModifiedtheses')->where('user_id', '[0-9]+');
                Route::get('/week/{week_id}', 'listModifiedthesesByWeek')->where('week_id', '[0-9]+');
                Route::get('/user/{user_id}/week/{week_id}', 'listUserModifiedthesesByWeek')
                    ->where('user_id', '[0-9]+')
                    ->where('week_id', '[0-9]+');
            });
        ########End Modified Theses ########

        ########Start ModificationReasons ########
        Route::controller(ModificationReasonController::class)
            ->prefix('modification-reasons')
            ->group(function () {
                Route::get('/leader', 'getReasonsForLeader');
            });
        ########End ModificationReasons ########

        #########UserException########
        Route::controller(UserExceptionController::class)
            ->prefix('userexception')
            ->group(function () {
                Route::post('/create', 'create');
                Route::get('/show/{exception_id}', 'show');
                Route::post('/update', 'update');
                Route::get('/cancel/{exception_id}', 'cancelException');
                Route::patch('/update-status/{exception_id}', 'updateStatus');
                Route::get('/listPindigExceptions', 'listPindigExceptions');
                Route::post('/addExceptions', 'addExceptions');
                Route::get('/finishedException', 'finishedException');
                Route::get('/user-exceptions/{user_id}', 'userExceptions');
                Route::get('/exceptions-filter/{filter}/{user_id}', 'exceptionsFilter');
                Route::post('/set-exceptional-freez', 'setExceptionalFreez');
                Route::post('/set-new-user', 'setNewUser');
                Route::get('/search-by-email/{email}', 'searchByEmail');
                Route::get('/list-by-advisor/{exception_type}/{advisor_id}', 'listForAdvisor');
                Route::put('/{exception_id}/assign-to-parent', 'AssignExceptionToParent');
                Route::post('/add-note', 'addNote');
                Route::get('/get-notes/{user_exception_id}', 'getNotes');
            });
        ############End UserException########

        ############ Start Group ############
        Route::controller(GroupController::class)
            ->prefix('group')
            ->group(function () {
                Route::get('/list-all/{retrieveType}/{name?}', 'listGroups');
                Route::get('/search-group-by-name/{name}', 'searchGroupByName');
                Route::post('/create', 'create');
                Route::get('/show/{group_id}', 'show');
                Route::get('/show-basic-info/{group_id}', 'showBasicInfo');
                Route::get('/group-by-type/{type}', 'GroupByType');
                Route::post('/update', 'update');
                Route::delete('/delete/{group_id}', 'delete');
                Route::get('/books/{group_id}', 'books');
                Route::get('/group-exceptions/{group_id}', 'groupExceptions');
                Route::get('/exceptions-filter/{filter}/{group_id}', 'exceptionsFilter');
                Route::get('/basic-mark-view/{group_id}/{week_id}', 'BasicMarksView');
                Route::get('/marathon-reading/{group_id}/{week_id}', 'MarathonReading');
                Route::get('/all-achievements/{group_id}/{week_id}', 'allAchievements');
                Route::get('/search-for-ambassador-achievement/{ambassador_name}/{group_id}/{week_filter?}', 'searchForAmbassadorAchievement');
                Route::get('/search-for-ambassador/{ambassador_name}/{group_id}', 'searchForAmbassador');
                Route::get('/achievement-as-pages/{group_id}/{week_id}', 'achievementAsPages');
                Route::post('/create-leader-request', 'createLeaderRequest');
                Route::get('/last-leader-request/{group_id}', 'lastLeaderRequest');
                Route::get('/audit-marks/{group_id}', 'auditMarks');
                Route::get('/user-groups', 'userGroups');
                Route::get('/statistics/{group_id}/{week_id}', 'statistics');
                Route::get('/theses-and-screens-by-week/{group_id}/{filter}', 'thesesAndScreensByWeek');
                Route::get('/month-achievement/{group_id}/{filter}', 'monthAchievement');
                Route::post('/assign-administrator', 'assignAdministrator');
                Route::post('/assign-supervisor', 'assignSupervisor');
                Route::get('/list-marathon-participants', 'getMarathonParticipants');
                Route::get('/current-ambassadors-count/{id}', 'currentAmbassadorsCount');
            });
        ############End Group############

        ########Start Activity########
        Route::controller(ActivityController::class)
            ->prefix('activity')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ########End Activity########

        ########Start Article########
        Route::controller(ArticleController::class)
            ->prefix('article')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
                Route::post('/articles-by-user', 'listAllArticlesByUser');
            });
        ########End Article########

        ########Start SocialMedia########
        Route::controller(SocialMediaController::class)
            ->prefix('socialMedia')
            ->group(function () {
                Route::post('/add-social-media', 'addSocialMedia');
            });
        ########End SocialMedia########

        ########Start Timeline ########
        Route::controller(TimelineController::class)
            ->prefix('timeline')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ########End Timeline ########

        ########Post########
        #updated RESTful routes by asmaa#
        Route::controller(PostController::class)
            ->prefix('posts')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'create');
                Route::get('/{id}', 'show')->where('id', '[0-9]+');
                Route::put('/{id}', 'update');
                Route::delete('/{id}', 'delete');

                Route::get('/timeline/{timeline_id}', 'postsByTimelineId')->where('timeline_id', '[0-9]+');
                Route::get('/users/{user_id}', 'postByUserId')->where('user_id', '[0-9]+');
                Route::get('/pending/timelines/{timeline_id}', 'listPostsToAccept')->where('timeline_id', '[0-9]+');

                Route::patch('/accept/{id}', 'acceptPost')->where('id', '[0-9]+');
                Route::patch('/decline/{id}', 'declinePost')->where('id', '[0-9]+');
                Route::patch('/{id}/control-comments', 'controlComments');
                Route::patch('/{id}/control-votes', 'controlVotes');
                Route::patch('/pin/{id}', 'pinPost')->where('id', '[0-9]+');

                Route::get('/home', 'getPostsForMainPage');
                Route::get('/announcements', 'getAnnouncements');
                Route::get('/support', 'getSupportPosts');
                Route::get('/support/latest', 'getLastSupportPost');
                Route::get('/friday-thesis', 'getFridayThesisPosts');
                Route::get('/friday-thesis/latest', 'getLastFridayThesisPost');
                Route::get('/pending/timeline/{timeline_id}/{post_id?}', 'getPendingPosts');
                Route::get('/current-week-support', 'getCurrentWeekSupportPost');
            });

        ########End Post########

        ########Poll-Vote########
        Route::controller(PollVoteController::class)
            ->prefix('poll-votes')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'create');
                Route::post('/show', 'show');
                Route::post('/votesByPostId', 'votesByPostId');
                Route::post('/votesByUserId', 'votesByUserId');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
                Route::get('/posts/{post_id}/users/{user_id?}', 'getPostVotesUsers')
                    ->where('post_id', '[0-9]+')
                    ->where('user_id', '[0-9]+');
            });
        ########End Poll-Vote########
        ########User-Profile########
        Route::controller(UserProfileController::class)
            ->prefix('user-profile')
            ->group(function () {
                Route::get('/show/{user_id}', 'show');
                Route::get('/show-to-update', 'showToUpdate');
                Route::get('/statistics/{user_id}', 'profileStatistics');
                Route::post('/update', 'update');
                Route::post('/update-profile-pic', 'updateProfilePic');
                Route::post('/update-profile-cover', 'updateProfileCover');
                Route::post('/update-official-document', 'updateOfficialDocument');
            });
        ########End User-Profile########

        ######## Statistics ########
        Route::controller(StatisticsController::class)
            ->prefix('statistics')
            ->group(function () {
                Route::get('/by-week/{week_id?}', 'byWeek');
                Route::get('/last-week', 'lastWeek');
                Route::get('/leaders-statistics/{superviser_id}/{week_id}', 'supervisingStatistics');
                Route::get('/supervisors-statistics/{advisor_id}/{week_id}', 'advisorsStatistics');
                Route::get('/advisors-statistics/{consultant_id}/{week_filter?}', 'consultantsStatistics');
                Route::get('/consultant-statistics/{admin_id}/{week_filter?}', 'administratorStatistics');
            });
        ######## End Statisticx########

        ########Profile-Setting########
        Route::controller(ProfileSettingController::class)
            ->prefix('profile-setting')
            ->group(function () {
                Route::post('/show', 'show');
                Route::post('/update', 'update');
            });
        ########End Profile-Setting########
        ####### Notification ########
        Route::controller(NotificationController::class)
            ->prefix('notifications')
            ->group(function () {
                Route::get('/list-all', 'listAllNotification');
                Route::get('/un-read', 'listUnreadNotification');
                Route::get('/mark-all-as-read', 'markAllAsRead');
                Route::get('/mark-as-read/{notification_id}', 'markAsRead');
            });
        ######## End Notification ########
        ####### Start UserGroup ########
        Route::controller(UserGroupController::class)
            ->prefix('user-group')
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/users/{group_id}', 'usersByGroupID');
                Route::post('/', 'create');
                Route::post('/show', 'show');
                Route::post('/add-member', 'addMember');
                Route::post('/assignRole', 'assign_role');
                Route::post('/updateRole', 'update_role');
                Route::post('/listUserGroup', 'list_user_group');
                Route::delete('/delete/{user_group_id}', 'delete');
                Route::post('/withdrawn', 'withdrawnMember');
                Route::get('/members-by-month/{group_id}/{month_filter}', 'membersByMonth');
            });
        ######## End UserGroup ########

        ####### Start Thesis ########
        Route::controller(ThesisController::class)
            ->prefix('theses')
            ->group(function () {
                Route::get('/{thesis_id}', 'show')->where('thesis_id', '[0-9]+');
                Route::get('/book/{book_id}/user/{user_id?}', 'listBookThesis')
                    ->where('book_id', '[0-9]+')
                    ->where('user_id', '[0-9]+');
                Route::get('/book/{book_id}/thesis/{thesis_id}', 'getBookThesis')
                    ->where('book_id', '[0-9]+')
                    ->where('thesis_id', '[0-9]+');
                Route::get('/user/{user_id}', 'listUserThesis')->where('user_id', '[0-9]+');
                Route::get('/week/{week_id}', 'listWeekThesis')->where('week_id', '[0-9]+');
                Route::post('/check-overlap', 'checkThesisOverlap');
            });
        ######## End Thesis ########

        ######## Start Week ########
        Route::controller(WeekController::class)
            ->prefix('weeks')
            ->group(function () {
                Route::post('/', 'create');
                Route::post('/update', 'update');
                Route::get('/', 'get_last_weeks_ids'); // for testing - to be deleted
                Route::get('/title', 'getDateWeekTitle');
                Route::post('/insert_week', 'insert_week');
                Route::get('/close-comments', 'closeBooksAndSupportComments');
                Route::get('/open-comments', 'openBooksComments');
                Route::get('/check-date', 'testDate');
                Route::patch('/update-exception/{exp_id}/{status}', 'update_exception_status');
                Route::get('/notify-users', 'notifyUsersNewWeek');
                Route::get('/get-weeks/{limit}', 'getWeeks');
                Route::get('/get-next-weeks-title/{limit}', 'getNextWeekTitles');
                Route::get('/weeks-around/{title}/{before?}/{after?}', 'getWeeksAroundTitle');
                Route::get('/get-previous-week', 'getPreviousWeek');
            });
        ######## End Week ########

        ######## Start Section ########
        Route::controller(SectionController::class)
            ->prefix('section')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ######## End Section ########

        ######## Book-Type ########
        Route::controller(BookTypeController::class)
            ->prefix('book-type')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ######## End Book-Type ########

        ######## Start Book-Level ########
        Route::controller(BookLevelController::class)
            ->prefix('book-level')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ######## End Book-Type ########

        ######## Start Book-Language ########
        Route::controller(LanguageController::class)
            ->prefix('language')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
            });
        ######## End Book-Language ########

        ######## Start Book-Suggestion ########
        Route::controller(BookSuggestionController::class)->prefix('book-suggestion')->group(function () {
            Route::post('/create', 'create');
            Route::post('/update-status', 'updateStatus');
            Route::get('/show/{suggestion_id}',  'show');
            Route::get('/list-by-status/{status}',  'listByStatus');
            Route::get('/is-allowed-to-suggest',  'isAllowedToSuggest');
        });
        ######## End Book-Suggestion ########


        ######## Start Exception-Type ########
        Route::controller(ExceptionTypeController::class)
            ->prefix('exception-type')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ######## End Exception-Type ########

        ######## Start Group-Type ########
        Route::controller(GroupTypeController::class)
            ->prefix('group-type')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ######## End Group-Type ########

        ######## Start Post-Type ########
        Route::controller(PostTypeController::class)
            ->prefix('post-type')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ######## End Post-Type ########

        ######## Start Thesis-Type ########
        Route::controller(ThesisTypeController::class)
            ->prefix('thesis-type')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ######## End Thesis-Type ########

        ######## Start Timeline-Type ########
        Route::controller(TimelineTypeController::class)
            ->prefix('timeline-type')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'create');
                Route::post('/show', 'show');
                Route::post('/update', 'update');
                Route::post('/delete', 'delete');
            });
        ######## End Timeline-Type ########

        ######## Start Room ########
        Route::controller(RoomController::class)
            ->prefix('rooms')
            ->group(function () {
                Route::post('/', 'create');
                Route::post('/addUserToRoom', 'addUserToRoom');
                Route::get('/', 'listRooms');
            });
        ######## End Room ########

        ######## Start Messages ########
        Route::controller(MessagesController::class)
            ->prefix('messages')
            ->group(function () {
                Route::post('/', 'create');
                Route::post('/room/{room_id}', 'setMessagesAsRead');
                Route::get('/room/{room_id}', 'listRoomMessages');
                Route::delete('/{message_id}', 'deleteMessage');
                Route::get('/unread-messages', 'unreadMessages');
            });
        ######## End Messages ########

        ######## Start Contact With Withdrawn ########
        Route::controller(ContactsWithWithdrawnController::class)->prefix('Contacts-with-withdrawn')->group(function () {
            Route::post('/send-email', 'sendEmail');
            Route::post('/update-contact-status', 'updateContactStatus');
            Route::get('/contact_has_been_made/{user_id}',  'showByUserID');
        });
        ######## End Contact With Withdrawn ########

        ######## Start BookStatistics ########
        Route::controller(BookStatisticsController::class)
            ->prefix('book-stat')
            ->group(function () {
                Route::get('/', 'index');
            });
        ######## End BookStatistics ########

        ######## Start WorkingHour ########
        Route::controller(WorkingHourController::class)
            ->prefix('working-hours')
            ->group(function () {
                Route::post('/', 'addWorkingHours');
                Route::get('/', 'getWorkingHours');
                Route::get('/statistics', 'getWorkingHoursStatistics');
            });
        ######## End WorkingHour ########

        ######## MarkNote ########
        Route::controller(MarkNoteController::class)
            ->prefix('mark-notes')
            ->group(function () {
                Route::get('/get-notes/{mark_id}', 'getNotes');
                Route::post('/create', 'create');
            });
        ######## End MarkNote ########

        ######## GeneralConversation ########
        Route::prefix('general-conversations')->group(function () {

            Route::controller(GeneralConversationController::class)
                ->prefix('questions')
                ->group(function () {
                    Route::get('/index', 'index');
                    Route::post('/', 'addQuestion');
                    Route::get('/', 'getAllQuestions');
                    Route::get('/{question_id}', 'getQuestionById')->where('question_id', '[0-9]+');
                    Route::get('/{question_id}/check-late', 'checkQuestionLate');
                    Route::put('/{question_id}/close', 'closeQuestion');
                    Route::put('/{question_id}/solve', 'solveQuestion');
                    Route::put('/{question_id}/assign-to-parent', 'AssignQuestionToParent');
                    Route::put('/{question_id}/move-to-discussion', 'moveQuestionToDiscussion');
                    Route::put('/{question_id}/move-to-questions', 'moveQuestionToQuestions');

                    Route::get('/my-questions', 'getMyQuestions');
                    Route::get('/my-active-questions', 'getMyActiveQuestions');
                    Route::get('/my-late-questions', 'getMyLateQuestions');
                    Route::get('/my-assigned-to-parent-questions', 'getMyAssignedToParentQuestions');
                    Route::get('/discussion-questions', 'getDiscussionQuestions');
                    Route::get('/statistics', 'getQuestionsStatistics');
                });

            Route::controller(GeneralConversationController::class)
                ->prefix('answers')
                ->group(function () {
                    Route::post('/', 'answerQuestion');
                });

            Route::controller(GeneralConversationController::class)
                ->group(function () {
                    Route::get('/exceptional-freez', 'getMyAssignedExceptionalFreez');
                });
        });
        ######## End GeneralConversation ########

        ######## Emptying ########

        Route::controller(TeamsDischargeController::class)->prefix('teams-discharge')->group(function () {
            // Route::post('/all/members', 'allMembersForEmptyingGroup');
            // Route::post('/move/ambassadors', 'moveGroupOfAmbassadors');
            // Route::post('/move/advisors', 'moveGroupOfAdvisors');
            // Route::post('/move/advisors', 'moveGroupOfSupervisors');
            // Route::post('/group', 'EmptyingGroup');

            Route::post('/discharge', 'discharge');
        });

        ########End Emptying ########

        /*
        |--------------------------------------------------------------------------|
        |                       Eligible API Routes                                |
        |--------------------------------------------------------------------------|
        */
        require __DIR__ . '/../routes/eligible.php';

        /*
        |--------------------------------------------------------------------------|
        |                       Ramadan API Routes                                |
        |--------------------------------------------------------------------------|
        */
        require __DIR__ . '/../routes/ramadan.php';

        /*
        |--------------------------------------------------------------------------|
        |                       Marathon API Routes                                |
        |--------------------------------------------------------------------------|
        */
        require __DIR__ . '/../routes/marathon.php';
    });
});
