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
use App\Models\ModifiedTheses;
use App\Models\Week;

class ModifiedThesesController extends Controller
{
    use ResponseJson;

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
     *Add a new reject theses to the system (“create mark” permission is required)
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modifier_reason_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'thesis_id' => 'required|numeric',
            'week_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('reject thesis')) {
            $input = $request->all();
            $input['modifier_id'] = Auth::id();
            ModifiedTheses::create($input);
            return $this->jsonResponseWithoutMessage("Modified Theses Craeted Successfully", 'data', 200);
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