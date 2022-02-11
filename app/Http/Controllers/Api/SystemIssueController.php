<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemIssue;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class SystemIssueController extends Controller
{
    use ResponseJson;

    public function index()
    {
        $issues = SystemIssue::all();
        if($issues){
            return $this->jsonResponseWithoutMessage($issues, 'data',200);
        }
        else {
            // throw new NotFound;
        }
    }

    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'reporter_id' => 'required|integer',
            'reporter_description' => 'required',
            'reviewer_id' => 'integer|nullable',
            'reviewer_note' => 'required|string',
            'status' => 'required|boolean',
            'solved' => 'date|nullable',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('create issue')){
            SystemIssue::create($request->all());
            return $this->jsonResponseWithoutMessage("System Issue Created Successfully", 'data', 201);
        }
        else{
            //throw new NotAuthorized;
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'issue_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $issue = SystemIssue::find($request->issue_id);
        if($issue){
            return $this->jsonResponseWithoutMessage($issue, 'data',200);
        }
        else{
            // throw new NotFound;
        }
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reporter_id' => 'required|integer',
            'reporter_description' => 'required',
            'reviewer_id' => 'integer|nullable',
            'reviewer_note' => 'required|string',
            'status' => 'required|boolean',
            'solved' => 'date|nullable',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit issue')){
            $issue = SystemIssue::find($request->issue_id);
            $issue->update($request->all());
            return $this->jsonResponseWithoutMessage("System Issue Updated Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;
        }

    }
}
