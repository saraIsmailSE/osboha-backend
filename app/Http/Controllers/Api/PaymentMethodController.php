<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    use ResponseJson;
    public function index(){
            $paymentMethod = PaymentMethod::get();
        return $this->jsonResponseWithoutMessage(PaymentMethodResource::collection($paymentMethod), 'data', 200);
    }

    public function create(Request $request){
        if(Auth::user()->hasRole('admin')){
            $validator = Validator::make($request->all(), [
                'name_ar' => 'required',
                'name_en' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
            }
            PaymentMethod::create($request->all());
            return $this->jsonResponseWithoutMessage("Created Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;
        }
    }


    public function update(Request $request){
        if(Auth::user()->hasRole('admin')){
            $validator = Validator::make($request->all(), [
                'name_ar' => 'required',
                'name_en' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
            }
            $paymentMethod= PaymentMethod::find($request->id);
            if(!$paymentMethod){
                throw new NotFound;
            }
            $paymentMethod->update($request->all());
            return $this->jsonResponseWithoutMessage("Updated Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;
        }

    }

    public function edit(Request $request){
        if(Auth::user()->hasRole('admin')){
            $paymentMethod= PaymentMethod::find($request->id);
            if(!$paymentMethod){
                throw new NotFound;
            }
            return $this->jsonResponseWithoutMessage(new PaymentMethodResource($paymentMethod), 'data', 200);
        }
        else{
            throw new NotAuthorized;
        }
    }

    public function delete(Request $request){
        if(Auth::user()->hasRole('admin')){
            $paymentMethod= PaymentMethod::find($request->id);
            if(!$paymentMethod){
                throw new NotFound;
            }
            $paymentMethod->delete();
            return $this->jsonResponseWithoutMessage("Deleted Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;
        }
    }
}
