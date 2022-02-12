<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    use ResponseJson;

    public function index()
    {
        $transactions = Transaction::all();
        if($transactions){
            return $this->jsonResponseWithoutMessage($transactions, 'data',200);
        }
        else {
            // throw new NotFound;
        }
    }

    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'role_id' => 'required|integer',
            'hiring_date' => 'required|date',
            'termination_reason' => 'required',
            'termination_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('create transaction')){
            Transaction::create($request->all());
            return $this->jsonResponseWithoutMessage("Transaction Created Successfully", 'data', 201);
        }
        else{
            //throw new NotAuthorized;
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $transaction = Transaction::find($request->transaction_id);
        if($transaction){
            return $this->jsonResponseWithoutMessage($transaction, 'data',200);
        }
        else{
            // throw new NotFound;
        }
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'role_id' => 'required|integer',
            'hiring_date' => 'required|date',
            'termination_reason' => 'required',
            'termination_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit transaction')){
            $transaction = Transaction::find($request->transaction_id);
            $transaction->update($request->all());
            return $this->jsonResponseWithoutMessage("Transaction Updated Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;
        }

    }
}
