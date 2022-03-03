<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use Spatie\Permission\Models\Role;

class TransactionController extends Controller
{
    use ResponseJson;

    public function index()
    {
        if(Auth::user()->can('list transactions')){
            $transactions = Transaction::all();
            if($transactions){
                return $this->jsonResponseWithoutMessage(TransactionResource::collection($transactions), 'data',200);
            }
            else {
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }
    }

    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'role_id' => 'required|integer',
            'hiring_date' => 'required|date',
            'termination_reason' => 'nullable',
            'termination_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('assign role')){
            Transaction::create($request->all());
            return $this->jsonResponseWithoutMessage("Transaction Created Successfully", 'data', 201);
        }
        else{
            throw new NotAuthorized;
        }
    }

    /*
     * Show a particular transaction
     */
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
            return $this->jsonResponseWithoutMessage(new TransactionResource($transaction), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

    /*
     * Show a list of transactions for a particular user
     */
    public function showUserTransactions(Request $request)
    {
        if(Auth::user()->can('list transactions')){
            $transactions = Transaction::all()->where('user_id', $request->user_id);
            if($transactions){
                return $this->jsonResponseWithoutMessage(TransactionResource::collection($transactions), 'data',200);
            }
            else {
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'role_id' => 'required|integer',
            'hiring_date' => 'required|date',
            'termination_reason' => 'required', //required?? maybe hiring date was wrong and needs to be updated
            'termination_date' => 'required|date', // required? same as above
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('update role')){
            $transaction = Transaction::find($request->transaction_id);
            if ($transaction) {
                // Delete role for user
                $role = Role::find($request->role_id);
                $role->delete();
                // Update termination details
                $transaction->update($request->all());
                return $this->jsonResponseWithoutMessage("Transaction Updated Successfully", 'data', 200);
            }
            else {
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }

    }
}
