<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\MarkResource;
use App\Models\User;
use App\Models\Week;
use Carbon\Carbon;
use App\libstats\MarksStats;
use Spatie\Stats\StatsQuery;

class MarkController extends Controller
{
    use ResponseJson;

    public function index()
    {
        if(Auth::user()->can('audit mark')){
            $current_week = Week::latest()->first();
            $marks = Mark::where('week_id', $current_week->id)->get();
        
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
               throw new NotFound;
            }
        } else {
            throw new NotAuthorized;   
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        if(Auth::user()->can('audit mark')){
            $mark = Mark::find($request->mark_id);
            if($mark){
                return $this->jsonResponseWithoutMessage(new MarkResource($mark), 'data',200);
            }
            else{
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;   
        } 
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'out_of_90' => 'required', 
            'out_of_100' => 'required', 
            'total_pages' => 'required',  
            'support' => 'required', 
            'total_thesis' => 'required', 
            'total_screenshot' => 'required',
            'mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('edit mark')){
            $mark = Mark::find($request->mark_id);
            if($mark){
                $mark->update($request->all());
                return $this->jsonResponseWithoutMessage("Mark Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;   
        }
    }

<<<<<<< HEAD
    public function list_user_marks(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'week_id' => 'nullable'
=======
    public function list_user_mark(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required_without:week_id',
            'week_id' => 'required_without:user_id'
>>>>>>> e295eaeef50f5eeeeaf7ce10e059a83aff2fce03
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
<<<<<<< HEAD
        if((Auth::user()->can('audit mark') || $request->user_id == Auth::id()) 
            & $request->week_id == null)
        {
            $marks = Mark::where('user_id', $request->user_id)->get();
=======
        if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
            && $request->has('week_id') && $request->has('user_id'))
        {
            $marks = Mark::where('user_id', $request->user_id)
                        ->where('week_id', $request->week_id)->get();
>>>>>>> e295eaeef50f5eeeeaf7ce10e059a83aff2fce03
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
                throw new NotFound;
            }
<<<<<<< HEAD
        }
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id()) 
                & $request->week_id != null)
        {
            $marks = Mark::where('user_id', $request->user_id)
                            ->where('week_id', $request->week_id)->get();
=======
        } 
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                && $request->has('week_id'))
        {
            $marks = Mark::where('week_id', $request->week_id)->get();
>>>>>>> e295eaeef50f5eeeeaf7ce10e059a83aff2fce03
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
<<<<<<< HEAD
=======
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                && $request->has('user_id'))
        {
            $mark = Mark::where('user_id', $request->user_id)->latest()->first();
            if($mark){
                return $this->jsonResponseWithoutMessage(new MarkResource($mark), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
>>>>>>> e295eaeef50f5eeeeaf7ce10e059a83aff2fce03
        else{
            throw new NotAuthorized;   
        }
    }
<<<<<<< HEAD
<<<<<<< HEAD

    // public function marks_by_weekid(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'week_id' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
    //     }

    //     if(Auth::user()->can('audit mark')){
    //         $marks = Mark::where('week_id', $request->week_id)->get();
    //         if($marks){
    //             return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
    //         }
    //         else{
    //             throw new NotFound;
    //         }
    //     } 
    //     else{
    //         throw new NotAuthorized;   
    //     }
    // }

    // public function marks_by_userid_and_weekid(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required',
    //         'week_id' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
    //     }

    //     if(Auth::user()->can('audit mark')){
    //         $marks = Mark::where('user_id', $request->user_id)
    //                     ->where('week_id', $request->week_id)->get();
    //         if($marks){
    //             return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
    //         }
    //         else{
    //             throw new NotFound;
    //         }
    //     } else{
    //         throw new NotAuthorized;   
    //     }
    // }
=======
>>>>>>> e295eaeef50f5eeeeaf7ce10e059a83aff2fce03
=======

    //its just example about the statistics changes in DB 
    public function statsMark(Request $request)
    {

         $stats = MarksStats::query()
        ->start(now()->subMonths($request->month))
        ->end(now()->subSecond())
        ->groupByMonth()
        ->get();

        return $stats;
       
    }
>>>>>>> ff98aa81e211e5ba9bfa5ea88d8be28e483fc63c
}
