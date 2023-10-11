<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Fqa;
use Illuminate\Support\Facades\Validator;



class EligibleFQAController extends Controller
{
    public function index()
    {

        $fqa = Fqa::all();
        return $this->sendResponse($fqa, "FQA");
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required',
            'answer' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $input = $request->all();

        try {
            $fqa = Fqa::create($input);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->sendError('FQA does not exist');
        }

        return $this->sendResponse($fqa, "FQA created");
    }


    public function show($id)
    {
        $fqa = FQA::find($id);

        if (is_null($fqa)) {

            return $this->sendError('FQA does not exist');
        }
        return $this->sendResponse($fqa, "FQA");
    }


    public function update(Request $request,  $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'question' => 'required',
            'answer' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors());
        }


        $fqa = Fqa::find($id);

        $updateParam = [
            "question" => $input['question'],
            "answer" => $input['answer'],

        ];

            $fqa->update($updateParam);

        return $this->sendResponse($fqa, 'FQA updated Successfully!');
    }

    public function destroy($id)
    {

        $fqa = Fqa::destroy($id);

        if ($fqa == 0) {

            return $this->sendError('FQA does not exist');
        }
        return $this->sendResponse($fqa, 'FQA deleted Successfully');
    }






}
