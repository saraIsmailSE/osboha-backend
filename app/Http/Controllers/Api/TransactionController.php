<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Models\UserGroup;
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

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'role_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $input = $request->all();
        // set hiring_date to today's date
        $input['hiring_date'] = date('Y-m-d');

        if(Auth::user()->can('assign role')){

            // Assign role to user
            $user = User::find($request->user_id);
            $role = Role::find($request->role_id);
            $user->assignRole($role);
           
            //join group -- Asmaa
            UserGroup::create([
                'user_id' => $request->user_id,
                'group_id' => $request->group_id,
                'user_type' => $role
            ]
            );
            Transaction::create($input);

            return $this->jsonResponseWithoutMessage("Transaction Created Successfully", 'data', 200);
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

        if ($transaction) {
            if(Auth::user()->can('list transactions') || Auth::id() == $transaction->user_id) {
                return $this->jsonResponseWithoutMessage(new TransactionResource($transaction), 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }

    /*
     * Show a list of transactions for a particular user
     */
    public function showUserTransactions(Request $request)
    {
        if(Auth::user()->can('list transactions') || Auth::id() == $request->user_id) {
            $transactions = Transaction::where('user_id', $request->user_id)->get();

            if($transactions->isNotEmpty()){
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
            'transaction_id' => 'required',
            'termination_reason' => 'required', // Admin will be able to enter the reason ONLY ONCE and won't be able to update it as this will update the termination_date
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $input = $request->all();
        // Set termination_date to today's date
        $input['termination_date'] = date('Y-m-d');

        if(Auth::user()->can('update role')){

            $transaction = Transaction::find($request->transaction_id);

            if ($transaction) {

                // Get user_id and role_id for that transaction
                $user_id = $transaction->user_id;
                $role_id = $transaction->role_id;

                // Remove role for user
                $user = User::find($user_id);
                $role = Role::find($role_id);
                $user->removeRole($role);

                //remove from group -- Asmaa
                $user_group = UserGroup::where('user_id', $user_id)->where('user_type', $role->name)->get();
                
                $user_group->delete(); //remove user from group

                // Update termination details
                $transaction->update($input);

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
