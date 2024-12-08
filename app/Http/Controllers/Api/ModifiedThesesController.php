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
            'week_id' => 'required_if:status,rejected|numeric',
            'modifier_reason_id' => 'required_if:status,rejected,rejected_parts|numeric',
            'thesis_id' => 'required|numeric',
            'status' => 'required|string|in:accepted,rejected,rejected_writing,accepted_one_thesis,rejected_parts',
            "rejected_parts" => "required_if:status,rejected_parts|numeric|in:1,2,3,4,5|nullable",
            "modified_thesis_id" => "exists:modified_theses,id|nullable"
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', 500);
        }
        if (Auth::user()->can('audit mark') && Auth::user()->hasRole([...config('constants.ALL_SUPPER_ROLES'), 'support_leader'])) {
            $week = Week::find($request->week_id);
            $modify_timer = $week->modify_timer;

            if (!$this->isValidDate($modify_timer)) {
                return $this->jsonResponseWithoutMessage("لا يمكنك تدقيق الأطروحة, لقد انتهى الأسبوع", 'data', Response::HTTP_NOT_ACCEPTABLE);
            }

            $thesis = Thesis::find($request->thesis_id);
            $thesis->status = $request->status;
            $thesis->rejected_parts = $request->rejected_parts;
            $thesis->save();

            if ($request->status !== 'accepted') {
                $input = [
                    'modifier_reason_id' => $request->modifier_reason_id,
                    'thesis_id' => $request->thesis_id,
                    'modifier_id' => Auth::id(),
                    'user_id' => $thesis->user_id,
                    'week_id' => $thesis->mark->week_id
                ];

                if ($request->modified_thesis_id) {
                    $modified_theses = ModifiedTheses::find($request->modified_thesis_id);
                    $modified_theses->update($input);
                } else {
                    $modified_theses = ModifiedTheses::create($input);
                }

                $thesis = $thesis->fresh();
                $this->calculateAllThesesMark($thesis->mark_id, true);

                $user = User::findOrFail($thesis->user_id);
                $reason = ModificationReason::findOrFail($request->modifier_reason_id);
                $user->notify(new RejectAmbassadorThesis($user->name, $reason->reason, $thesis->book_id, $thesis->id));
            } else {
                if ($request->modified_thesis_id) {
                    $modified_theses = ModifiedTheses::find($request->modified_thesis_id);
                    $modified_theses->delete();

                    $thesis->status = 'accepted';
                    $thesis->rejected_parts = null;
                    $thesis->save();

                    $this->calculateAllThesesMark($thesis->mark_id, true);
                }
            }

            //send notification to user
            $message = '';

            if ($request->status === 'accepted') {
                $message = 'تم قبول أطروحتك من قِبَل ' . Auth::user()->name;
            } else if ($request->status === 'rejected') {
                $message = 'تم رفض أطروحتك كاملة مع القراءة من قِبَل ' . Auth::user()->name;
            } else if ($request->status === 'rejected_writing') {
                $message = 'تم رفض أطروحتك كاملة بدون القراءة من قِبَل ' . Auth::user()->name;
            } else if ($request->status === 'accepted_one_thesis') {
                $message = 'تم قبول ورد واحد فقط من أطروحتك من قِبَل ' . Auth::user()->name;
            }

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
        if ($user->can('audit mark') && ($user->hasRole(['advisor', 'supervisor', 'admin']))) {
            $modified_theses = ModifiedTheses::find($request->rejected_theses_id);
            if ($modified_theses) {
                $input = [
                    ...$request->only('head_modifier_reason_id', 'status'),
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
