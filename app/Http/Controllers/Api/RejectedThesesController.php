<?php

namespace App\Http\Controllers\Api;

use App\Models\RejectedTheses;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\RejectedThesesResource;
use App\Models\Week;
use Carbon\Carbon;

class RejectedThesesController extends Controller
{
    use ResponseJson;

     /**
     * Read all rejected theses in the current week in the system(“audit mark” permission is required)
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        if(Auth::user()->can('audit mark')){
            $current_week = Week::latest()->first();
            $rejected_theses = RejectedTheses::where('week_id', $current_week->id)->get();
        
            if($rejected_theses){
                return $this->jsonResponseWithoutMessage(RejectedThesesResource::collection($rejected_theses), 'data',200);
            }
            else{
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
            'rejecter_note' => 'required', 
            'user_id' => 'required',
            'thesis_id' => 'required', 
            'week_id' => 'required', 
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }   
        
        if(Auth::user()->can('reject mark')){
            $input=$request->all();
            $input['rejecter_id']= Auth::id();
            RejectedTheses::create($input);
            return $this->jsonResponseWithoutMessage("Rejected Theses Craeted Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;   
        }
    }

    /**
     * Find and show an existing rejected theses in the system by its id  ( “audit mark” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rejected_theses_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
 
        if(Auth::user()->can('audit mark')){
            $rejected_theses = RejectedTheses::find($request->rejected_theses_id);
            if($rejected_theses){
                return $this->jsonResponseWithoutMessage(new RejectedThesesResource($rejected_theses), 'data',200);
            }
            else{
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
            'rejecter_note' => 'required', 
            'is_acceptable' => 'required',
            'rejected_theses_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('audit mark')){
            $rejected_theses = RejectedTheses::find($request->rejected_theses_id);
            if($rejected_theses){
                $rejected_theses->update($request->all());
                return $this->jsonResponseWithoutMessage("Rejected Theses Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;   
        }
    }
    /**
     * Return list of user rejected theses (”audit mark” permission is required OR request user_id == Auth).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function list_user_rejectedtheses(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required_without:week_id',
            'week_id' => 'required_without:user_id'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
            && $request->has('week_id') && $request->has('user_id'))
        {
            $rejected_theses = RejectedTheses::where('user_id', $request->user_id)
                                            ->where('week_id', $request->week_id)->get();
            if($rejected_theses){
                return $this->jsonResponseWithoutMessage(RejectedThesesResource::collection($rejected_theses), 'data',200);
            }
            else{
                throw new NotFound;
            }
        } 
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                && $request->has('week_id'))
        {
            $rejected_theses = RejectedTheses::where('week_id', $request->week_id)->get();
            if($rejected_theses){
                return $this->jsonResponseWithoutMessage(RejectedThesesResource::collection($rejected_theses), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                && $request->has('user_id'))
        {
            $rejected_theses = RejectedTheses::where('user_id', $request->user_id)->latest()->first();
            if($rejected_theses){
                return $this->jsonResponseWithoutMessage(new RejectedThesesResource($rejected_theses), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;   
        }    
    }
}
