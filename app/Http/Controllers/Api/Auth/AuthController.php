<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
//use App\Http\Controllers\Api\NotificationController;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Sign_up;
use App\Models\UserProfile;
use App\Models\ProfileSetting;
use App\Models\LeaderRequest;
use App\Models\Group;
use App\Models\UserGroup;
use App\Events\NewUserStats;
use App\Http\Controllers\Api\NotificationController;
use App\Models\Mark;
use App\Models\Thesis;
use App\Models\Timeline;
use App\Models\TimelineType;
use App\Models\UserBook;
use App\Models\UserException;
use App\Models\UserParent;
use App\Models\Week;
use App\Notifications\MailDowngradeRole;
use App\Notifications\MailUpgradeRole;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    use ResponseJson;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $authUser = Auth::user();

            $success['token'] = $authUser->createToken('sanctumAuth')->plainTextToken;
            $success['user'] = $authUser->load('userProfile', 'roles:id,name', 'roles.permissions:id,name');

            return $this->jsonResponseWithoutMessage($success, 'data', 200);
        } else {

            return $this->jsonResponseWithoutMessage('البريد الالكتروني او كلمة المرور غير صحيحة', 'data', 201);
        }
    }


    public function signUp(Request $request)
    {
        $ambassador = Validator::make($request->all(), [
            'name'             => 'required',
            'gender'           => 'required',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required',
        ]);
        if ($ambassador->fails()) {
            return $this->jsonResponseWithoutMessage($ambassador->errors(), 'data', 500);
        }
        try {
            DB::beginTransaction();

            $user = new User($request->all());
            $user->password = bcrypt($request->input('password'));
            $user->assignRole('ambassador');
            $user->save();

            //create new timeline - type = profile
            $profileTimeline = TimelineType::where('type', 'profile')->first();
            $timeline = new Timeline();
            $timeline->type_id = $profileTimeline->id;
            $timeline->save();

            //create user profile, with profile settings
            UserProfile::create([
                'user_id' => $user->id,
                'timeline_id' => $timeline->id
            ]);
            ProfileSetting::create([
                'user_id' => $user->id,
            ]);

            event(new Registered($user));

            $success['token'] = $user->createToken('sanctumAuth')->plainTextToken;
            $success['user'] = $user->load('userProfile', 'roles:id,name', 'roles.permissions:id,name');

            DB::commit();
            return $this->jsonResponseWithoutMessage($success, 'data', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return $this->jsonResponseWithoutMessage('User already exist', 'data', 202);
            } else {
                Log::channel('newUser')->info($e);
                DB::rollBack();
                return $this->jsonResponseWithoutMessage('حدث خطأ، يرجى المحاولة فيما بعد', 'data', 202);
            }
        }
    }


    public function signUp_v2(Request $request)
    {
        $ambassador = Validator::make($request->all(), [
            'name'             => 'required',
            'last_name'           => 'required',
            'gender'           => 'required',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required',
        ]);
        if ($ambassador->fails()) {
            return $this->jsonResponseWithoutMessage($ambassador->errors(), 'data', 500);
        }
        try {
            DB::beginTransaction();

            $user = new User($request->all());
            $user->password = bcrypt($request->input('password'));
            $user->assignRole('ambassador');
            $user->save();

            //create new timeline - type = profile
            $profileTimeline = TimelineType::where('type', 'profile')->first();
            $timeline = new Timeline();
            $timeline->type_id = $profileTimeline->id;
            $timeline->save();

            //create user profile, with profile settings
            UserProfile::create([
                'user_id' => $user->id,
                'timeline_id' => $timeline->id
            ]);
            ProfileSetting::create([
                'user_id' => $user->id,
            ]);

            event(new Registered($user));

            $success['token'] = $user->createToken('sanctumAuth')->plainTextToken;
            $success['user'] = $user->load('userProfile', 'roles:id,name', 'roles.permissions:id,name');

            DB::commit();
            return $this->jsonResponseWithoutMessage($success, 'data', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return $this->jsonResponseWithoutMessage('User already exist', 'data', 202);
            } else {
                Log::channel('newUser')->info($e);
                DB::rollBack();
                return $this->jsonResponseWithoutMessage('حدث خطأ، يرجى المحاولة فيما بعد', 'data', 202);
            }
        }
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return $this->jsonResponseWithoutMessage('You are Logged Out Successfully', 'data', 200);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $token = $user->createToken('sanctumAuth')->plainTextToken;
        return $this->jsonResponseWithoutMessage($token, 'data', 200);
    }

    protected function sendResetLinkResponse(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT)
            return $this->jsonResponseWithoutMessage("تفقد بريدك الالكتروني", 'data', 200);
        else
            return $this->jsonResponseWithoutMessage("  حدث خطأ", 'data', 200);
    }

    protected function sendResetResponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                /**
                 * @todo: slow query - asmaa
                 */
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->createToken('random key')->accessToken;

                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET)
            return $this->jsonResponseWithoutMessage($status, 'Updated Successfully!', 200);
        else
            return $this->jsonResponseWithoutMessage($status, 'data', 500);
    }

    /**
     *get auth user session data for frontend.
     *
     * @return jsonResponse;
     */

    public function sessionData()
    {
        // latest books whereHas 'status'='in progress'
        $book_in_progress = UserBook::where('status', 'in progress')
            ->where('user_id', Auth::id())
            ->latest()
            ->limit(3)
            ->get();

        if ($book_in_progress) {
            foreach ($book_in_progress as $key => $book) {
                $response['book_in_progress'][$key] = $book->book;
                $last_thesis = Thesis::where('user_id', Auth::id())
                    ->where('book_id', $book->book_id)
                    ->with('book')
                    ->latest()->first();
                if ($last_thesis) {
                    $response['progress'][$key] = ($last_thesis->end_page / $last_thesis->book->end_page) * 100;
                } else {
                    $response['progress'][$key] = 0;
                }
            }
        } else {
            $response['book_in_progress'] = null;
            $response['progress'] = null;
        }

        //reading group [where auth is ambassador]
        $response['reading_team'] = UserGroup::where('user_id', Auth::id())
            ->where('user_type', 'ambassador')
            ->whereNull('termination_reason')
            ->with('group')
            ->first();

        //Parent Of Auth
        $response['parent'] = User::find(Auth::user()->parent_id);

        //main timer
        $response['timer'] = Week::latest()->first();

        //Last Exception
        $response['last_exception'] = UserException::with(['week', 'type'])->without('reviewer')->where('user_id', Auth::id())
            ->whereIn('status', ['accepted'])
            ->first();

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function getRoles($id)
    {
        $authRoles = Auth::user()->load('roles:id,name');
        $authLastrole = $authRoles->roles->first();
        $rolesToRetrieve = config('constants.rolesToRetrieve');

        $roles = Role::whereIn('name', $rolesToRetrieve[$authLastrole->name])->orderBy('id', 'desc')->get();
        return $this->jsonResponseWithoutMessage($roles, 'data', 200);
    }


    /**
     *return excluded member [FOR NOW].
     *
     * @return jsonResponse;
     * @todo: slow query - asmaa
     */

    public function returnToTeam()
    {
        //update is_excluded \ is_hold to 0
        $user = User::Find(Auth::id());

        $userGroup = UserGroup::where('user_id', $user->id)->where('user_type', 'ambassador')->latest()->first();
        if ($userGroup) {
            DB::beginTransaction();
            try {
                $leader = UserGroup::where('group_id', $userGroup->group_id)->where('user_type', 'leader')->whereNull('termination_reason')->first();
                if ($leader) {
                    $user->parent_id = $leader->user_id;
                    $user->request_id = 0;
                    $user->is_excluded = 0;
                    $user->is_hold = 0;
                    $user->save();

                    $created_at = $this->determineWeekStartForCreation();

                    UserParent::create([
                        'user_id' => $user->id,
                        'parent_id' => $leader->user_id,
                        'is_active' => 1,
                        'created_at' => $created_at,
                        'updated_at' => $created_at,
                    ]);
                    UserGroup::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'group_id' =>  $userGroup->group_id,
                            'user_type' => 'ambassador',
                            'termination_reason' => null
                        ],
                        [
                            'user_id' => $user->id,
                            'group_id' =>  $userGroup->group_id,
                            'user_type' => 'ambassador',
                            'termination_reason' => null,
                            'created_at' => $created_at,
                            'updated_at' => $created_at,

                        ]
                    );
                    DB::commit();

                    $userGroupCacheKey = 'user_group_' . $user->id;
                    Cache::forget($userGroupCacheKey);


                    $notification = new NotificationController();
                    $msg = 'قام السفير ' . $user->name . ' بالعودة إلى الفريق';
                    $notification->sendNotification($user->parent_id, $msg, EXCLUDED_USER);

                    return $this->jsonResponseWithoutMessage('تم التعديل بنجاح', 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage('لا يوجد قائد حالي لمجموعتك، يرجى مراسلة فريق الدعم', 'data', 201);
                }
            } catch (\Exception $e) {
                Log::channel('newUser')->info($e);
                DB::rollBack();
                return $this->jsonResponseWithoutMessage($e->getMessage() . ' at line ' . $e->getLine(), 'data', 500);
            }
        } else {
            return $this->jsonResponseWithoutMessage('لست سفيراً في اي مجموعة، يرجى مراسلة قائدك السابقك', 'data', 201);
        }
    }


    private function determineWeekStartForCreation()
    {
        $today = now();
        $dayOfWeek = $today->dayOfWeek; //Sunday is 0, Monday is 1, and so on, up to Saturday as 6.

        //Sunday check before 12PM
        $isSundayBeforeNoon = ($dayOfWeek === 0 && $today->lt($today->copy()->setHour(12)->setMinute(0)->setSecond(0)));

        // Determine if we start next week
        $goToNextWeek = in_array($dayOfWeek, [4, 5, 6]) || ($dayOfWeek === 0 && !$isSundayBeforeNoon);

        if ($goToNextWeek) {
            $yearWeeks = config('constants.YEAR_WEEKS');
            foreach ($yearWeeks as $week) {
                $weekStart = Carbon::parse($week['date'])->setHour(12)->setMinute(1)->setSecond(0);
                if ($weekStart->greaterThan($today)) {
                    return $weekStart->format('Y-m-d H:i:s');
                }
            }
        } else {
            return $today->format('Y-m-d H:i:s');
        }
    }
    /**
     *reset auth email.
     *
     * @return jsonResponse;
     */

    public function resetEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        try {
            DB::beginTransaction();

            $user = User::find(Auth::id());
            $user->email = $request->email;
            $user->email_verified_at = null;
            $user->save();
            //sendEmailVerificationNotification
            $user->sendEmailVerificationNotification();
            DB::commit();

            return $this->jsonResponseWithoutMessage('Reset Successfully!', 'data', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return $this->jsonResponseWithoutMessage('User already exist', 'data', 201);
            } else {
                Log::channel('newUser')->info($e);
                DB::rollBack();
                return $this->jsonResponseWithoutMessage('حدث خطأ، يرجى المحاولة فيما بعد', 'data', 201);
            }
        }
    }
}
