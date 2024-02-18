<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exports\WorkingHoursExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnswerResource;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\UserInfoResource;
use App\Http\Resources\UserResource;
use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionAssignee;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Week;
use App\Models\WorkHour;
use App\Models\WorkingHour;
use App\Rules\base64OrImage;
use App\Rules\base64OrImageMaxSize;
use App\Traits\MediaTraits;
use App\Traits\PathTrait;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Maatwebsite\Excel\Facades\Excel;

class GeneralConversationController extends Controller
{
    use ResponseJson, PathTrait, MediaTraits;
    protected $perPage;

    public function __construct()
    {
        $this->perPage = 25;
    }

    public function addQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "question" => "required|string",
            "media" => "nullable|array",
            "media.*" => [
                new base64OrImage(),
                new base64OrImageMaxSize(2 * 1024 * 1024),
            ],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', Response::HTTP_BAD_REQUEST);
        }

        //check permission
        if (Auth::user()->hasAnyRole(config('constants.ALL_SUPPER_ROLES'))) {
            $user = Auth::user();

            try {
                DB::beginTransaction();

                $question = Question::create([
                    "question" => $request->question,
                    "user_id" => $user->id,
                    "current_assignee_id" => $user->parent_id,
                ]);

                //save media
                if ($request->has('media')) {
                    $folder_path = 'questions/' . Auth::id();
                    foreach ($request->media as $media) {
                        $this->createMedia($media, $question->id, 'question', $folder_path);
                    }
                }

                //save assignee
                $question->assignees()->create([
                    "assigned_by" => $user->id, //the user who created the question
                    "assignee_id" => $user->parent_id,
                ]);

                DB::commit();

                $question = $question->fresh();

                $message = "لقد قام المستخدم " . $user->name . " بطرح سؤال جديد وتعيينه لك للإجابة عليه";

                //notify the assignee
                $notification = new NotificationController();
                $notification->sendNotification($user->parent_id, $message, 'Questions', $this->getGeneralConversationPath($question->id));

                return $this->jsonResponseWithoutMessage(QuestionResource::make($question), 'data', Response::HTTP_OK);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', Response::HTTP_BAD_REQUEST);
            }
        }

        throw new NotAuthorized;
    }

    public function getMyQuestions()
    {
        $user = Auth::user();

        if ($user->hasAnyRole(config('constants.ALL_SUPPER_ROLES'))) {
            $questions = Question::where(function ($query) use ($user) {
                $query->where("user_id", $user->id)->orWhere("current_assignee_id", $user->id);
            })->with('answers', function ($query) {
                $query->where('is_discussion', false);
            })->paginate($this->perPage);

            if ($questions->isEmpty()) {
                return $this->jsonResponse(
                    [],
                    'data',
                    Response::HTTP_OK,
                    "لا يوجد تحويلات"
                );
            }

            return $this->jsonResponseWithoutMessage([
                "questions" => QuestionResource::collection($questions),
                "total" => $questions->total(),
                "last_page" => $questions->lastPage(),
                "has_more_pages" => $questions->hasMorePages(),
            ], 'data', Response::HTTP_OK);
        }

        throw new NotAuthorized;
    }

    public function getMyActiveQuestions()
    {
        $user = Auth::user();

        if ($user->hasAnyRole(config('constants.ALL_SUPPER_ROLES'))) {

            $questions = Question::where(function ($query) use ($user) {
                $query->where("user_id", $user->id)->orWhere("current_assignee_id", $user->id);
            })->whereIn("status", ["open", "discussion"])->with('answers', function ($query) {
                $query->where('is_discussion', false);
            })->paginate($this->perPage);

            if ($questions->isEmpty()) {
                return $this->jsonResponse(
                    [],
                    'data',
                    Response::HTTP_OK,
                    "لا يوجد تحويلات فعالة"
                );
            }

            return $this->jsonResponseWithoutMessage([
                "questions" => QuestionResource::collection($questions),
                "total" => $questions->total(),
                "last_page" => $questions->lastPage(),
                "has_more_pages" => $questions->hasMorePages(),
            ], 'data', Response::HTTP_OK);
        }

        throw new NotAuthorized;
    }

    public function getMyLateQuestions()
    {
        //late questions are the questions that are not answered for 12 hours from the moment of the assigning the question to the user

        $user = Auth::user();

        if ($user->hasAnyRole(config('constants.ALL_SUPPER_ROLES'))) {
            //check if the question is assigned to the user and late(12 hours from the question assigning time)
            // $questions = Question::whereHas('assignees', function ($query) use ($user) {
            //     $query->where('assignee_id', $user->id)
            //         ->where('is_active', true)
            //         ->where('created_at', '<', now()->subHours(12));
            // })
            //     ->whereIn("status", ["open", "discussion"])

            //     //check if the user didn't answer the question within 12 hours from the question assigning time
            //     ->whereDoesntHave('answers', function ($query) use ($user) {
            //         $query->where('user_id', $user->id)
            //             ->where('is_discussion', false)
            //             //answered before 12 hours from the question assigning time
            //             ->where('created_at', '<', DB::raw("DATE_ADD(questions_assignees.created_at, INTERVAL 12 HOUR)"));
            //     })
            //     ->with('answers', function ($query) {
            //         $query->where('is_discussion', false);
            //     })
            //     ->paginate($this->perPage);

            $questions =  $this->getUsersLateQuestions([$user->id]);
            // Question::join('questions_assignees', 'questions.id', '=', 'questions_assignees.question_id')
            //     ->leftJoin('answers', function ($join) use ($user) {
            //         $join->on('questions.id', '=', 'answers.question_id')
            //             ->where('answers.user_id', '=', $user->id)
            //             ->where('answers.is_discussion', '=', false)
            //             ->whereRaw('answers.created_at < DATE_ADD(questions_assignees.created_at, INTERVAL 12 HOUR)');
            //     })
            //     ->where('questions_assignees.assignee_id', $user->id)
            //     ->where('questions_assignees.is_active', true)
            //     ->where('questions_assignees.created_at', '<', now()->subHours(12))
            //     ->whereIn('questions.status', ['open', 'discussion'])
            //     ->whereNull('answers.id')
            //     ->with(['answers' => function ($query) {
            //         $query->where('is_discussion', false);
            //     }])
            //     ->paginate($this->perPage);

            if ($questions->isEmpty()) {
                return $this->jsonResponse(
                    [],
                    'data',
                    Response::HTTP_OK,
                    "لا يوجد تحويلات متأخرة"
                );
            }

            return $this->jsonResponseWithoutMessage([
                "questions" => QuestionResource::collection($questions),
                "total" => $questions->total(),
                "last_page" => $questions->lastPage(),
                "has_more_pages" => $questions->hasMorePages(),
            ], 'data', Response::HTTP_OK);
        }
    }

    public function getAllQuestions()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'consultant', 'advisor'])) {
            throw new NotAuthorized;
        }

        [$allUsers, $keyword] = $this->getUsersBasedOnRole($user, true);

        if (count($allUsers) === 0) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_OK,
                "لا يوجد تحويلات متأخرة لدى " . $keyword
            );
        }

        // $questions = Question::whereIn('current_assignee_id', $allUsers)
        //     ->whereIn("status", ["open", "discussion"])
        //     ->where('created_at', '<=', now()->subHours(12))
        //     // ->whereDoesntHave('answers', function ($query) use ($allUsers) {
        //     //     $query->whereIn('user_id', $allUsers)
        //     //         ->where('is_discussion', false)
        //     //         ->where('created_at', '<', DB::raw("DATE_ADD(questions.created_at, INTERVAL 12 HOUR)"));
        //     // })
        //     ->with('answers', function ($query) {
        //         $query->where('is_discussion', false);
        //     })
        //     ->orderBy('created_at', 'desc')->paginate($perPage);

        $questions =  $this->getUsersLateQuestions($allUsers);

        if ($questions->isEmpty()) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_OK,
                "لا يوجد تحويلات متأخرة لدى " . $keyword
            );
        }

        return $this->jsonResponseWithoutMessage([
            "questions" => QuestionResource::collection($questions),
            "total" => $questions->total(),
            "last_page" => $questions->lastPage(),
            "has_more_pages" => $questions->hasMorePages(),

        ], 'data', Response::HTTP_OK);
    }

    public function getDiscussionQuestions()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'consultant', 'advisor'])) {
            throw new NotAuthorized;
        }

        $questions = Question::where('status', 'discussion')
            ->with('answers', function ($query) {
                $query->where('is_discussion', true);
            })->paginate($this->perPage);

        if ($questions->isEmpty()) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_OK,
                "لا يوجد تحويلات مناقشة"
            );
        }

        return $this->jsonResponseWithoutMessage([
            "questions" => QuestionResource::collection($questions),
            "total" => $questions->total(),
            "last_page" => $questions->lastPage(),
            "has_more_pages" => $questions->hasMorePages(),
        ], 'data', Response::HTTP_OK);
    }

    public function getQuestionById($question_id)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'consultant', 'advisor', 'supervisor', 'leader'])) {
            throw new NotAuthorized;
        }

        $question = Question::where('id', $question_id)
            ->with('answers', function ($query) {
                $query->where('is_discussion', false);
            })->first();

        if (!$question) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_OK,
                "التحويل غير موجود"
            );
        }

        return $this->jsonResponseWithoutMessage(
            new QuestionResource($question),
            'data',
            Response::HTTP_OK
        );
    }

    public function answerQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "answer" => "required|string",
            "question_id" => "required|integer|exists:questions,id",
            "is_discussion" => "integer|in:0,1|nullable",
            "media" => "nullable|array",
            "media.*" => [
                new base64OrImage(),
                new base64OrImageMaxSize(2 * 1024 * 1024),
            ],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', Response::HTTP_BAD_REQUEST);
        }

        $user = Auth::user();
        if ($user->hasAnyRole(config('constants.ALL_SUPPER_ROLES'))) {
            $question = Question::find($request->question_id);

            $answer = $question->answers()->create([
                "answer" => $request->answer,
                "user_id" => $user->id,
                "is_discussion" => $request->has('is_discussion') ? $request->is_discussion : false,
            ]);

            //save media
            if ($request->has('media')) {
                $folder_path = 'answers/' . $request->question_id . '/' . Auth::id();
                foreach ($request->media as $media) {
                    $this->createMedia($media, $answer->id, 'answer', $folder_path);
                }
            }

            $message = "لقد قام " . $user->name . " بالإجابة على سؤالك";

            //notify the owner
            $notification = new NotificationController();
            $notification->sendNotification($question->user_id, $message, 'Questions', $this->getGeneralConversationPath($question->id));

            return $this->jsonResponseWithoutMessage(AnswerResource::make($answer), 'data', Response::HTTP_OK);
        }

        throw new NotAuthorized;
    }

    public function solveQuestion($question_id)
    {
        $question = Question::find($question_id);

        if (!$question) {
            return $this->jsonResponseWithoutMessage("التحويل غير موجود", 'data', Response::HTTP_NOT_FOUND);
        }

        if (Auth::id() === $question->user_id || Auth::user()->hasAnyRole(['admin', 'consultant', 'advisor'])) {

            $question->update([
                'status' => 'solved',
                'closed_at' => now()
            ]);

            $notification = new NotificationController();
            //notify the assignee
            $assigneeMessage = "لقد تمت الإجابة على السؤال الذي تم تعيينك للإجابة عليه وإغلاقه";
            $notification->sendNotification($question->current_assignee_id, $assigneeMessage, 'Questions', $this->getGeneralConversationPath($question->id));

            return $this->jsonResponseWithoutMessage(
                QuestionResource::make($question),
                'data',
                200
            );
        }

        throw new NotAuthorized;
    }

    public function AssignQuestionToParent($question_id)
    {
        $user = Auth::user();

        $question = Question::find($question_id);

        if (!$question) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_NOT_FOUND,
                "التحويل غير موجود"
            );
        }

        $parent = User::find($user->parent_id);

        if (!$parent) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_NOT_FOUND,
                "المشرف غير موجود"
            );
        }

        //check if the parent is already assigned to the question
        $assignee = $question->assignees()->where('assignee_id', $parent->id)->first();

        if ($assignee) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_FORBIDDEN,
                "المشرف معين بالفعل على هذا السؤال"
            );
        }

        try {
            DB::beginTransaction();

            //set the current assignee to inactive
            $question->assignees()->where('assignee_id', $user->id)->update([
                "is_active" => false
            ]);

            //create new assignee
            $question->assignees()->create([
                "assigned_by" => $user->id, //the current assignee
                "assignee_id" => $parent->id,
            ]);

            //update the current assignee
            $question->current_assignee_id = $parent->id;
            $question->save();

            DB::commit();

            $messageToUser = "لقد قام المستخدم " . $user->name . " بتعيين سؤالك للمشرف " . $parent->name;
            $messageToNewAssignee = "لقد تم تعيينك من قبل المستخدم " . $user->name . " للإجابة على سؤال المستخدم " . $question->user->name;

            //notify the assignee
            $notification = new NotificationController();
            $notification->sendNotification($question->user_id, $messageToUser, 'Questions', $this->getGeneralConversationPath($question->id));
            $notification->sendNotification($parent->id, $messageToNewAssignee, 'Questions', $this->getGeneralConversationPath($question->id));

            return $this->jsonResponseWithoutMessage(QuestionResource::make($question), 'data', Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_BAD_REQUEST,
                "حدث خطأ أثناء تعيين المشرف"
            );
        }
    }

    public function moveQuestionToDiscussion($question_id)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'consultant'])) {
            throw new NotAuthorized;
        }

        $question = Question::where('id', $question_id)->where('status', 'open')->first();

        if (!$question) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_NOT_FOUND,
                "التحويل غير موجود"
            );
        }

        $question->status = 'discussion';
        $question->save();

        $message = "لقد قام المستخدم " . $user->name . " بتحويل سؤالك للمناقشة";
        (new NotificationController())->sendNotification($question->user_id, $message, 'Questions', $this->getGeneralConversationPath($question->id));
    }

    public function moveQuestionToQuestions($question_id)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'consultant'])) {
            throw new NotAuthorized;
        }

        $question = Question::where('id', $question_id)->where('status', 'discussion')->first();

        if (!$question) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_NOT_FOUND,
                "التحويل غير موجود"
            );
        }

        $question->status = 'open';
        $question->save();

        $message = "لقد قام المستخدم " . $user->name . " بإعادة سؤالك للتحويل";
        (new NotificationController())->sendNotification($question->user_id, $message, 'Questions', $this->getGeneralConversationPath($question->id));
    }

    public function checkQuestionLate($question_id)
    {
        $question = Question::find($question_id);

        if (!$question) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_NOT_FOUND,
                "التحويل غير موجود"
            );
        }


        $is_late = false;
        if ($question->status == "solved") {
            $createdAt = Carbon::parse($question->created_at);
            $closedAt = Carbon::parse($question->closed_at);
            $is_late = $closedAt->diffInHours($createdAt) >= 12;
        } else {

            $assignee = $question->currentAssignee;
            $lastAnswer = $question->answers->where('user_id', $assignee->id)->where('is_discussion', false)->first();
            if ($lastAnswer) {
                //question is answered late if the answer comes after 12 hours of the question
                $createdAt = Carbon::parse($question->created_at);
                $answeredAt = Carbon::parse($lastAnswer->created_at);
                $is_late = $answeredAt->diffInHours($createdAt) >= 12;
            } else {
                //check if question is late (not answered for 12 hours)
                $is_late = Carbon::now()->diffInHours($question->created_at) >= 12;
            }
        }

        return $this->jsonResponseWithoutMessage(
            ["is_late" => $is_late],
            'data',
            Response::HTTP_OK
        );
    }

    public function getQuestionsStatistics(Request $request)
    {
        /*
        في صفحة الاحصائيات، نحتاج ظهور جدول الاحصائية للمسؤول عن كل الذين هم ضمن مسؤوليته
         (الأسبوع الحالي\ الأسبوع الماضي\الشهر الحالي\ الشهرين التي يسبقها).
        وتلزم هذه المعلومات في الاحصائيات .
        عدد مجموع التحويلات التي وصلت هذا الشخص (مراقب\موجه\مستشار).
        ** عدد التحويلات التي تم إجابتها بعد مرور 12 ساعة عند هذا الشخص
        *** عدد التحويلات الفعالة حتى هذه اللحظة عند هذا الشخص
        **** عدد التحويلات التي تمت إجابتها عند هذا الشخص .
        ***** عدد التحويلات التي قام هذا الشخص برفعها لمسؤوله الأعلى
        */

        $auth = Auth::user();

        if (!$auth->hasAnyRole(['admin', 'consultant', 'advisor'])) {
            throw new NotAuthorized;
        }

        //get date stats
        [$response, $weeks] = $this->getDateStats($request);

        //get users based on the auth user role
        [$allUsers,, $advisors] = $this->getUsersBasedOnRole($auth, false, $auth->hasRole('admin'));

        if ($weeks->count() === 0 || ($allUsers->count() === 0 && $advisors->count() === 0)) {
            return $this->jsonResponseWithoutMessage(
                $response,
                'data',
                Response::HTTP_OK
            );
        }

        $weeks = $weeks->get();
        //get the beginning created_at of weeks
        $startDate = $weeks->first()->created_at;
        $endDate = $weeks->last()->main_timer;

        ##### Start fetching data -- for all users#####

        //get all users questions stats
        $response['statistics'] = $this->getUsersQuestionsStats($allUsers, $startDate, $endDate);

        //in case of ADMIN, get advisors data
        $response['advisorsStatistics'] = $this->getUsersQuestionsStats($advisors, $startDate, $endDate);

        return $this->jsonResponseWithoutMessage(
            $response,
            'data',
            Response::HTTP_OK
        );
    }

    //Function to get the late questions for users
    private function getUsersLateQuestions($users)
    {
        //id advisor, get questions assigned to his/her supervisors
        //if consultant, get questions assigned to his/her advisors
        //if admin, get questions assigned to all consultants

        return Question::select('questions.*')
            ->join('questions_assignees', 'questions.id', '=', 'questions_assignees.question_id')
            ->leftJoin('answers', function ($join) use ($users) {
                $join->on('questions.id', '=', 'answers.question_id')
                    ->whereIn('answers.user_id', $users)
                    ->where('answers.is_discussion', '=', false)
                    ->whereRaw('answers.created_at < DATE_ADD(questions.created_at, INTERVAL 12 HOUR)');
            })
            ->whereIn('questions_assignees.assignee_id', $users)
            ->where('questions_assignees.is_active', true)
            ->where('questions_assignees.created_at', '<', now()->subHours(12))
            ->whereIn('questions.status', ['open', 'discussion'])
            ->whereNull('answers.id')
            ->with(['answers' => function ($query) {
                $query->where('is_discussion', false);
            }, 'currentAssignee', 'media', 'user'])
            ->orderBy('questions.created_at', 'desc')
            ->paginate($this->perPage);
    }

    // Function to get the users based on the auth user role
    private function getUsersBasedOnRole($user, $justIds = false, $withAdvisorsForAdmin = false)
    {
        $allUsers = [];
        $advisors = [];
        $keyword = "المستشارين";

        //if the user is an admin, display all questions
        if ($user->hasRole('admin')) {
            //get all consultants
            $allUsers = User::role('consultant');

            $keyword = "المستشارين";

            if ($withAdvisorsForAdmin) {
                $advisors = User::role('advisor');
            }
        } else if ($user->hasRole('consultant')) {
            //get consultants' groups
            $consultantGroups = UserGroup::where('user_id', $user->id)
                ->whereNull('termination_reason')
                ->where('user_type', 'consultant')
                ->pluck('group_id')->toArray();

            //get all advisors
            $allUsers = User::role('advisor')->whereHas('groups', function ($query) use ($consultantGroups) {
                $query->whereIn('group_id', $consultantGroups);
            });

            $keyword = "الموجهين";
        } else if ($user->hasRole('advisor')) {
            //get advisors' groups
            $advisorGroups = UserGroup::where('user_id', $user->id)
                ->whereNull('termination_reason')
                ->where('user_type', 'advisor')
                ->pluck('group_id')->toArray();

            //get all supervisors (which are in the same advising team)
            $allUsers = User::role('supervisor')->whereHas('groups', function ($query) use ($advisorGroups) {
                $query->whereIn('group_id', $advisorGroups);
            });

            $keyword = "المراقبين";
        }

        if ($justIds) {
            $allUsers = $allUsers->orderBy('name', 'asc')->pluck('id')->toArray();

            if ($withAdvisorsForAdmin) {
                $advisors = $advisors->orderBy('name', 'asc')->pluck('id')->toArray();
            }
        } else {
            $allUsers = $allUsers->orderBy('name', 'asc')->get();

            if ($withAdvisorsForAdmin) {
                $advisors = $advisors->orderBy('name', 'asc')->get();
            }
        }

        return [$allUsers, $keyword, $advisors];
    }

    //Function to get the date related data and weeks for the statistics
    private function getDateStats($request)
    {
        $response = [];

        //get last 2 weeks
        $response['weeks'] = Week::orderBy('created_at', 'desc')->take(2)->get();

        //get last 3 months with their years and arabic names
        $response['months'] = [];
        for ($i = 0; $i < 3; $i++) {
            $month = Carbon::now()->subMonths($i)->month;
            $year = Carbon::now()->subMonths($i)->year;
            $response['months'][] = [
                "date" => $year . "-" . $month,
                'title' => config('constants.ARABIC_MONTHS')[$month] . " " . $year,
            ];
        }

        //inputs
        $type = $request->type ? $request->type : "week";
        $selectedDate = $request->date ? $request->date : Week::latest()->first()->id;

        $response['selectedType']  = $type;
        $response['selectedDate'] = $selectedDate;

        $weeks = null;
        $month = null;

        if ($type === "week") {
            $weeks = Week::where('id', $selectedDate)->orderBy('created_at', 'asc');
        } else {
            $date = Carbon::parse($selectedDate)->toDateString();
            $month = Carbon::parse($date)->month;
            $year = Carbon::parse($date)->year;

            $response['monthTitle'] = "شهر " . config('constants.ARABIC_MONTHS')[$month];
            $weeks =  Week::whereYear('created_at', '<=', $year)
                ->whereYear('main_timer', '>=', $year)
                ->whereMonth('created_at', '<=', $month)
                ->whereMonth('main_timer', '>=', $month)
                ->orderBy('created_at', 'asc');
        }

        $response['selectedMonth'] = $month;

        return [$response, $weeks];
    }

    //Function to get users questions statistics
    private function getUsersQuestionsStats($users, $startDate, $endDate)
    {
        $questionsStats = [];
        foreach ($users as $user) {
            $userData = UserInfoResource::make($user);

            $baseUserQuestions = $this->getUserQuestionsQuery($user, $startDate, $endDate);

            $questionsStats[] = [
                "user" => $userData,
                "total_questions" => $baseUserQuestions->count(),
                "total_solved_questions_after_12_hrs" => $this->getSolvedQuestionsCount($baseUserQuestions, '12', $user, $startDate, $endDate),
                "total_active_questions" => $this->getActiveQuestionsCount($baseUserQuestions),
                "total_solved_questions" => $this->getSolvedQuestionsCount($baseUserQuestions, null, $user, $startDate, $endDate),
                "total_questions_assigned_to_parent" => $this->getQuestionsAssignedToParentCount($user, $startDate, $endDate),
            ];
        }

        return $questionsStats;
    }

    // Function to get the base user questions query
    private function getUserQuestionsQuery($user, $startDate, $endDate)
    {
        return Question::whereHas('assignees', function ($query) use ($user, $startDate, $endDate) {
            $query->where('assignee_id', $user->id)
                ->where('is_active', true)
                ->whereBetween('created_at', [$startDate, $endDate]);
        })->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Function to get the count of solved questions
    private function getSolvedQuestionsCount($query, $hours = null, $user = null, $startDate = null, $endDate = null)
    {
        $solvedQuestionsQuery = clone $query;

        if ($hours !== null) {
            return  Question::join('questions_assignees', 'questions.id', '=', 'questions_assignees.question_id')
                ->join('answers', function ($join) use ($hours, $user) {
                    $join->on('questions.id', '=', 'answers.question_id')
                        ->where('answers.user_id', $user->id)
                        ->where('answers.is_discussion', false)
                        ->whereRaw("answers.created_at < DATE_ADD(questions.created_at, INTERVAL $hours HOUR)");
                })
                ->where('questions_assignees.assignee_id', $user->id)
                ->where('questions_assignees.is_active', true)
                ->whereBetween('questions_assignees.created_at', [$startDate, $endDate])
                ->whereBetween('questions.created_at', [$startDate, $endDate])
                ->count();
        } else {
            return $solvedQuestionsQuery->where('status', 'solved')
                ->orWhereHas('answers', function ($query) use ($user, $startDate, $endDate) {
                    $query->where('user_id', $user->id)
                        ->where('is_discussion', false)
                        ->whereBetween('created_at', [$startDate, $endDate]);
                })->count();
        }
    }

    // Function to get the count of active questions
    private function getActiveQuestionsCount($query)
    {
        $activeQuestionsQuery = clone $query;
        return $activeQuestionsQuery->whereIn('status', ['open', 'discussion'])->count();
    }

    // Function to get the count of questions assigned to parent
    private function getQuestionsAssignedToParentCount($user, $startDate, $endDate)
    {
        return Question::where('user_id', '!=', $user->id)
            ->whereHas('assignees', function ($query) use ($user, $startDate, $endDate) {
                $query->where('assigned_by', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })->count();
    }
}
