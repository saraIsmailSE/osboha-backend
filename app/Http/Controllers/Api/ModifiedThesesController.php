<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\ModifiedThesesResource;
use App\Models\ModificationReason;
use App\Models\ModifiedTheses;
use App\Models\Thesis;
use App\Models\User;
use App\Models\Week;
use App\Notifications\RejectAmbassadorThesis;
use App\Traits\PathTrait;
use App\Traits\ThesisTraits;
use Illuminate\Http\Response;

class ModifiedThesesController extends Controller
{
    use ResponseJson, ThesisTraits, PathTrait;

    /**
     * Read all rejected theses in the current week in the system(“audit mark” permission is required)
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        if (Auth::user()->can('audit mark')) {
            $current_week = Week::latest()->first();
            $rejected_theses = ModifiedTheses::where('week_id', $current_week->id)->get();

            if ($rejected_theses) {
                return $this->jsonResponseWithoutMessage(ModifiedThesesResource::collection($rejected_theses), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Add a new reject theses to the system (“audit mark” permission is required)
     *    
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modifier_reason_id' => 'required_if:status,rejected,one_thesis|numeric',
            'thesis_id' => 'required|numeric',
            'status' => 'required|string|in:accepted,rejected,one_thesis',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }        
        if (Auth::user()->can('audit mark') && Auth::user()->hasRole(['advisor', 'supervisor', 'leader', "admin"])) {
            //get lastest week
            $current_week = Week::latest()->first();
            $main_timer = $current_week->main_timer;

            //check if current date(full) greater than main timer
            if (date('Y-m-d H:i:s') > $main_timer) {
                return $this->jsonResponseWithoutMessage("لا يمكنك تدقيق الأطروحة, لقد انتهى الأسبوع", 'data', Response::HTTP_NOT_ACCEPTABLE);
            }

            $thesis = Thesis::find($request->thesis_id);
            $thesis->status = $request->status;
            $thesis->save();

            if ($request->status !== 'accepted') {
                $input['modifier_reason_id'] = $request->modifier_reason_id;
                $input['thesis_id'] = $request->thesis_id;
                $input['modifier_id'] = Auth::id();
                $input['user_id'] = $thesis->user_id;
                $input['week_id'] = $thesis->mark->week_id;

                ModifiedTheses::create($input);
                $this->auditThesis($thesis, $request->status);

                $user = User::findOrFail($thesis->user_id);
                $reason = ModificationReason::findOrFail($request->modifier_reason_id);
                $user->notify(new RejectAmbassadorThesis($user->name, $reason->reason, $thesis->book_id, $thesis->id));
            }

            //send notification to user
            $arabicStatus = '';

            if ($request->status === 'approved') {
                $arabicStatus = 'قبول';
            } else if ($request->status === 'rejected') {
                $arabicStatus = 'رفض';
            } else if ($request->status === 'one_thesis') {
                $arabicStatus = 'قبول علامة واحدة من';
            }

            $message = 'تم ' . $arabicStatus . ' أطروحتك من قِبَل ' . Auth::user()->name;

            (new NotificationController)->sendNotification($thesis->user_id, $message, ACHIEVEMENTS, $this->getThesesPath($thesis->book_id, $thesis->id));
            return $this->jsonResponseWithoutMessage("تم تدقيق الأطروحة بنجاح, وإعلام السفير", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Find and show an existing rejected theses in the system by its id  ( “audit mark” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show($id)
    {
        if (Auth::user()->can('audit mark')) {
            $rejected_theses = ModifiedTheses::find($id);
            if ($rejected_theses) {
                return $this->jsonResponseWithoutMessage(new ModifiedThesesResource($rejected_theses), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Update an existing rejected theses ( audit mark” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'head_modifier_reason_id' => 'required|numeric',
            'status' => 'required|string|in:accepted,rejected',
            'modified_theses_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $user = Auth::user();
        if ($user->can('audit mark') && ($user->hasRole('advisor') || $user->hasRole('supervisor') || $user->hasRole('admin'))) {
            $modified_theses = ModifiedTheses::find($request->rejected_theses_id);
            if ($modified_theses) {
                $input = [
                    ...$request->all(),
                    'head_modifier_id' => $user->id
                ];
                $modified_theses->update($input);
                return $this->jsonResponseWithoutMessage("Modified Theses Updated Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * Return list of user rejected theses (”audit mark” permission is required OR request user_id == Auth).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function listUserModifiedthesesByWeek($user_id, $week_id)
    {
        if (Auth::user()->can('audit mark') || $user_id == Auth::id()) {
            $rejected_theses = ModifiedTheses::where('user_id', $user_id)
                ->where('week_id', $week_id)->get();
            if ($rejected_theses) {
                return $this->jsonResponseWithoutMessage(ModifiedThesesResource::collection($rejected_theses), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function listUserModifiedtheses($user_id)
    {
        if (Auth::user()->can('audit mark') || $user_id == Auth::id()) {
            $rejected_theses = ModifiedTheses::where('user_id', $user_id)->get();
            if ($rejected_theses) {
                return $this->jsonResponseWithoutMessage(ModifiedThesesResource::collection($rejected_theses), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function listModifiedthesesByWeek($week_id)
    {
        if (Auth::user()->can('audit mark')) {
            $rejected_theses = ModifiedTheses::where('week_id', $week_id)->get();
            if ($rejected_theses) {
                return $this->jsonResponseWithoutMessage(ModifiedThesesResource::collection($rejected_theses), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
}