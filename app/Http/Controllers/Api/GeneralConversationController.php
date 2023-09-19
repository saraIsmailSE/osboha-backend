<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnswerResource;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\UserInfoResource;
use App\Models\Group;
use App\Models\Question;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Week;
use App\Models\WorkHour;
use App\Models\WorkingHour;
use App\Traits\PathTrait;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class GeneralConversationController extends Controller
{
    use ResponseJson, PathTrait;
    protected $perPage;

    public function __construct()
    {
        $this->perPage = 25;
    }

    public function addQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "question" => "required|string",
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', Response::HTTP_BAD_REQUEST);
        }

        //check permission
        if (Auth::user()->hasAnyRole(['admin', 'consultant', 'advisor', 'supervisor'])) {
            $user = Auth::user();

            $question = Question::create([
                "question" => $request->question,
                "user_id" => $user->id,
                "assignee_id" => $user->parent_id,
            ]);

            $message = "لقد قام المستخدم " . $user->name . " بطرح سؤال جديد وتعيينه لك للإجابة عليه";

            //notify the assignee
            $notification = new NotificationController();
            $notification->sendNotification($user->parent_id, $message, 'Questions', $this->getGeneralConversationPath($question->id));

            return $this->jsonResponseWithoutMessage(QuestionResource::make($question), 'data', Response::HTTP_OK);
        }

        throw new NotAuthorized;
    }

    public function answerQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "answer" => "required|string",
            "question_id" => "required|integer|exists:questions,id",
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', Response::HTTP_BAD_REQUEST);
        }

        $user = Auth::user();
        if ($user->hasAnyRole(['admin', 'consultant', 'advisor', 'supervisor', 'leader'])) {
            $question = Question::find($request->question_id);

            $answer = $question->answers()->create([
                "answer" => $request->answer,
                "user_id" => $user->id,
            ]);

            $message = "لقد قام " . $user->name . " بالإجابة على سؤالك";

            //notify the owner
            $notification = new NotificationController();
            $notification->sendNotification($question->user_id, $message, 'Questions', $this->getGeneralConversationPath($question->id));

            return $this->jsonResponseWithoutMessage(AnswerResource::make($answer), 'data', Response::HTTP_OK);
        }

        throw new NotAuthorized;
    }

    public function closeOverdueQuestions()
    {
        $questions = Question::where('status', 'open')->where('created_at', '<=', now()->subHours(48))->get();

        if ($questions->isEmpty()) {
            return $this->jsonResponseWithoutMessage("No overdue questions found", 'data', Response::HTTP_OK);
        }

        $notification = new NotificationController();
        foreach ($questions as $question) {
            $question->update([
                'status' => 'closed'
            ]);

            //notify the owner
            $OwnerMessage = "لقد تم إغلاق سؤالك بسبب عدم الإجابة عليه";
            $notification->sendNotification($question->user_id, $OwnerMessage, 'Questions', $this->getGeneralConversationPath($question->id));

            //notify the assignee
            $assigneeMessage = "لقد تم إغلاق السؤال الذي تم تعيينك للإجابة عليه بسبب عدم الإجابة عليه";
            $notification->sendNotification($question->assignee_id, $assigneeMessage, 'Questions', $this->getGeneralConversationPath($question->id));
        }


        return $this->jsonResponseWithoutMessage("Overdue questions marked closed", 'data', Response::HTTP_OK);
    }

    public function closeQuestion($question_id)
    {
        $question = Question::find($question_id);

        if (!$question) {
            return $this->jsonResponseWithoutMessage("Question not found", 'data', Response::HTTP_NOT_FOUND);
        }

        if (Auth::id() === $question->user_id  || Auth::id() === $question->assignee_id || Auth::user()->hasRole("admin")) {
            $question->update([
                'status' => 'closed'
            ]);

            $notification = new NotificationController();
            //notify the owner
            $OwnerMessage = "لقد تم إغلاق سؤالك بسبب عدم الإجابة عليه";
            $notification->sendNotification($question->user_id, $OwnerMessage, 'Questions', $this->getGeneralConversationPath($question->id));

            //notify the assignee
            $assigneeMessage = "لقد تم إغلاق السؤال الذي تم تعيينك للإجابة عليه بسبب عدم الإجابة عليه";
            $notification->sendNotification($question->assignee_id, $assigneeMessage, 'Questions', $this->getGeneralConversationPath($question->id));

            return $this->jsonResponseWithoutMessage([
                'status' => $question->status,
                'updated_at' => $question->updated_at
            ], 'data', 200);
        }

        throw new NotAuthorized;
    }

    public function solveQuestion($question_id)
    {
        $question = Question::find($question_id);

        if (!$question) {
            return $this->jsonResponseWithoutMessage("Question not found", 'data', Response::HTTP_NOT_FOUND);
        }

        if (Auth::id() === $question->user_id  || Auth::id() === $question->assignee_id || Auth::user()->hasRole("admin")) {

            $question->update([
                'status' => 'solved'
            ]);

            $notification = new NotificationController();
            //notify the owner
            $OwnerMessage = "لقد تمت الإجابة على سؤالك وإغلاقه";
            $notification->sendNotification($question->user_id, $OwnerMessage, 'Questions', $this->getGeneralConversationPath($question->id));

            //notify the assignee
            $assigneeMessage = "لقد تمت الإجابة على السؤال الذي تم تعيينك للإجابة عليه وإغلاقه";
            $notification->sendNotification($question->assignee_id, $assigneeMessage, 'Questions', $this->getGeneralConversationPath($question->id));

            return $this->jsonResponseWithoutMessage([
                'status' => $question->status,
                'updated_at' => $question->updated_at
            ], 'data', 200);
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
                "السؤال غير موجود"
            );
        }

        if ($question->assignee_id != $user->id) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_FORBIDDEN,
                "لا يمكنك تعيين هذا السؤال"
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

        $question->assignee_id = $parent->id;
        $question->save();

        $messageToUser = "لقد قام المستخدم " . $user->name . " بتعيين سؤالك للمشرف " . $parent->name;
        $messageToNewAssignee = "لقد تم تعيينك من قبل المستخدم " . $user->name . " للإجابة على سؤال المستخدم " . $question->user->name;

        //notify the assignee
        $notification = new NotificationController();
        $notification->sendNotification($question->user_id, $messageToUser, 'Questions', $this->getGeneralConversationPath($question->id));
        $notification->sendNotification($parent->id, $messageToNewAssignee, 'Questions', $this->getGeneralConversationPath($question->id));

        return $this->jsonResponseWithoutMessage([
            "assignee" => UserInfoResource::make($parent)
        ], 'data', Response::HTTP_OK);
    }

    public function getQuestions()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'consultant', 'advisor', 'supervisor', 'leader'])) {
            throw new NotAuthorized;
        }

        $questions = null;

        //get user group
        $userGroups = $user->groups()->whereNull('user_groups.termination_reason')->pluck('group_id')->toArray();
        // dd($userGroups);
        //if leader, get his/her questions only
        //if supervisor, get his/her questions, others supervisors question (which are in the same advising team) and his/her leaders questions
        //id advisor, get his/her questions and his/her supervisors questions and their leaders questions
        //if consultant, get his/her questions and his/her advisors questions and their supervisors questions and their leaders questions
        //if admin, get all questions

        $perPage = $this->perPage;

        //if the user is an admin, display all questions
        if ($user->hasRole('admin')) {
            $questions = Question::orderBy('created_at', 'desc')->paginate($perPage);
        } else if ($user->hasRole('consultant')) {
            //get all consultants
            $consultants = User::role('consultant')->pluck('id')->toArray();

            //get all advisors
            $advisors = User::role('advisor')->pluck('id')->toArray();

            //get all supervisors (which are in the same consulting team)
            $supervisors = User::role('supervisor')->whereHas('groups', function ($query) use ($userGroups) {
                $query->whereIn('group_id', $userGroups);
            })->pluck('id')->toArray();

            //get all leaders (which are in the same consulting team)
            $leaders = User::role('leader')->whereHas('groups', function ($query) use ($userGroups) {
                $query->whereIn('group_id', $userGroups);
            })->pluck('id')->toArray();

            $allUsers = array_merge($consultants, $advisors, $supervisors, $leaders);

            $questions = Question::whereIn('user_id', $allUsers)->orderBy('created_at', 'desc')->paginate($perPage);
        } else if ($user->hasRole('advisor')) {
            //get all advisors           
            $advisors = User::role('advisor')->pluck('id')->toArray();

            //get all supervisors (which are in the same advising team)
            $supervisors = User::role('supervisor')->whereHas('groups', function ($query) use ($userGroups) {
                $query->whereIn('group_id', $userGroups);
            })->pluck('id')->toArray();

            //get all leaders (which are in the same advising team)
            $leaders = User::role('leader')->whereHas('groups', function ($query) use ($userGroups) {
                $query->whereIn('group_id', $userGroups);
            })->pluck('id')->toArray();

            $allUsers = array_merge($advisors, $supervisors, $leaders);

            $questions = Question::whereIn('user_id', $allUsers)->orderBy('created_at', 'desc')->paginate($perPage);
        } else if ($user->hasRole('supervisor')) {

            $advisorGroup = UserGroup::where('user_id', $user->parent_id)
                ->whereNull('termination_reason')
                ->where('user_type', 'advisor')->pluck('group_id')->toArray();

            //get all supervisors (which are in the same advising team)
            $supervisors = User::role('supervisor')
                ->whereHas('groups', function ($query) use ($advisorGroup) {
                    $query->whereIn('group_id', $advisorGroup)
                        ->where("user_type", "ambassador");
                })
                ->pluck('id')->toArray();

            //get all leaders (which are in the same advising team)
            $leaders = User::role('leader')
                ->whereHas('groups', function ($query) use ($userGroups) {
                    $query->whereIn('group_id', $userGroups)
                        ->where("user_type", "ambassador");
                })->pluck('id')->toArray();

            $allUsers = array_merge($supervisors, $leaders);

            $questions = Question::whereIn('user_id', $allUsers)->orderBy('created_at', 'desc')->paginate($perPage);
        } else if ($user->hasRole('leader')) {
            //get leaders' questions
            $questions = Question::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')->paginate($perPage);
        }

        if ($questions->isEmpty()) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_OK,
                "لا يوجد أسئلة"
            );
        }

        return $this->jsonResponseWithoutMessage([
            "questions" => QuestionResource::collection($questions),
            "total" => $questions->total(),
            "last_page" => $questions->lastPage(),
            "has_more_pages" => $questions->hasMorePages(),

        ], 'data', Response::HTTP_OK);
    }

    public function getAssignedToMeQuestions()
    {
        $user = Auth::user();

        $questions = Question::where("assignee_id", $user->id)->paginate($this->perPage);

        if ($questions->isEmpty()) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_OK,
                "لا يوجد أسئلة معينة لك"
            );
        }

        return $this->jsonResponseWithoutMessage([
            "questions" => QuestionResource::collection($questions),
            "total" => $questions->total(),
            "last_page" => $questions->lastPage(),
            "has_more_pages" => $questions->hasMorePages(),
        ], 'data', Response::HTTP_OK);
    }

    public function getMyQuestions()
    {
        $user = Auth::user();

        $questions = Question::where("user_id", $user->id)->paginate($this->perPage);

        if ($questions->isEmpty()) {
            return $this->jsonResponse(
                [],
                'data',
                Response::HTTP_OK,
                "لا يوجد أسئلة مطروحة من قبلك"
            );
        }

        return $this->jsonResponseWithoutMessage([
            "questions" => QuestionResource::collection($questions),
            "total" => $questions->total(),
            "last_page" => $questions->lastPage(),
            "has_more_pages" => $questions->hasMorePages(),
        ], 'data', Response::HTTP_OK);
    }

    public function getQuestionsStatistics()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'consultant', 'advisor'])) {
            throw new NotAuthorized;
        }

        $previousWeek = Week::orderBy('created_at', 'desc')->skip(1)->first();
        $created_at = $previousWeek->created_at;
        $main_timer = $previousWeek->main_timer;

        //get questions between the created_at of the previous week and the main_timer of the previous week

        $total = Question::whereBetween('created_at', [$created_at, $main_timer])->count();

        $totalOpened = Question::where("status", "open")->whereBetween('created_at', [$created_at, $main_timer])->count();

        $totalClosed = Question::where("status", "closed")->whereBetween('created_at', [$created_at, $main_timer])->count();

        $totalSolved = Question::where("status", "solved")->whereBetween('created_at', [$created_at, $main_timer])->count();

        //get total solved within 12 hours (difference between created_at and updated_at)
        $totalSolvedWithin12hrs =  Question::where("status", "solved")->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 12')
            ->whereBetween('created_at', [$created_at, $main_timer])->count();

        //get total solved after 12 hours (difference between created_at and updated_at)
        $totalSolvedAfter12hrs =  Question::where("status", "solved")
            ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) > 12')
            ->whereBetween('created_at', [$created_at, $main_timer])->count();


        return $this->jsonResponseWithoutMessage([
            "week" => $previousWeek,
            "total" => $total,
            "totalOpened" => $totalOpened,
            "totalClosed" => $totalClosed,
            "totalSolved" => $totalSolved,
            "totalSolvedWithin12hrs" => $totalSolvedWithin12hrs,
            "totalSolvedAfter12hrs" => $totalSolvedAfter12hrs,
        ], 'data', Response::HTTP_OK);
    }

    public function addWorkingHours(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'minutes' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(
                [
                    "dataTypeOFMinutes" => gettype($request->minutes),
                ],
                'data',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()->first()
            );
        }

        $user = Auth::user();

        //find working hours for today, if not found create new one else update it by adding to the current minutes
        $workingHours = WorkHour::where("user_id", $user->id)->whereDate("created_at", Carbon::today())->first();

        if (!$workingHours) {
            $workingHours = WorkHour::create([
                "user_id" => $user->id,
                "minutes" => $request->minutes,
            ]);
        } else {
            $workingHours->minutes += $request->minutes;
            $workingHours->save();
        }

        return $this->jsonResponseWithoutMessage([
            "workingHours" => $workingHours,
        ], 'data', Response::HTTP_OK);
    }

    public function getWorkingHours()
    {
        $user = Auth::user();

        if (!Auth::user()->hasAnyRole(['admin', 'consultant', 'advisor', 'supervisor'])) {
            throw new NotAuthorized;
        }

        $currentWeek = Week::latest()->first();
        $created_at = $currentWeek->created_at;
        $main_timer = $currentWeek->main_timer;

        //get all working hours grouped by created_at date
        $workingHours = WorkHour::where("user_id", $user->id)
            ->whereBetween('created_at', [$created_at, $main_timer])
            ->get();

        $totalMinutes = $workingHours->sum(function ($item) {
            return $item->sum('minutes');
        });


        return $this->jsonResponseWithoutMessage(
            [
                "workingHours" => $workingHours,
                "totalMinutes" => $totalMinutes,
            ],
            'data',
            Response::HTTP_OK
        );
    }

    public function getWorkingHoursStatistics()
    {
        if (!Auth::user()->hasAnyRole(['admin', 'consultant', 'advisor'])) {
            throw new NotAuthorized;
        }

        // $previousWeek = Week::orderBy('created_at', 'desc')->skip(1)->first();
        $previousWeek = Week::latest()->first();
        $created_at = $previousWeek->created_at;
        $main_timer = $previousWeek->main_timer;

        //get all working hours grouped by created_at date and sorted based on the user roles
        $workingHours = WorkHour::whereBetween('created_at', [$created_at, $main_timer])
            ->get()
            ->groupBy(function ($item) {
                return $item->user->roles->pluck('name')->first();
            })
            ->map(function ($item) {
                return $item->groupBy('user_id');
            });



        $totalMinutes = $workingHours->sum(function ($item) {
            return $item->sum('minutes');
        });

        return $this->jsonResponseWithoutMessage(
            [
                "workingHours" => $workingHours,
                "totalMinutes" => $totalMinutes,
            ],
            'data',
            Response::HTTP_OK
        );
    }
}
