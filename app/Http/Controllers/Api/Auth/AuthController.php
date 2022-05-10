<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\NotificationController;
use App\Models\User;
use App\Models\Sign_up;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;



class AuthController extends Controller
{
    use ResponseJson;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $authUser = Auth::user();
            $success['token'] = $authUser->createToken('sanctumAuth')->plainTextToken;
            $success['user'] = $authUser;

            return $this->jsonResponse($success, 'data', 200, 'Login Successfully');
        } else {

            return $this->jsonResponse('UnAuthorized', 'data', 404, 'Email Or Password is Wrong');
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'name_ar'          => 'required',
            // 'name_en'          => 'required',
            'name'             => 'required',
            'gender'           => 'required',
            'leader_gender'    => 'required',
            'phone'            => 'required|numeric',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required',
            'user_type'        => 'required',
         ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
       $input = $request->all();
       $input['password'] = bcrypt($input['password']);
       $this->allocateAmbassador($input);

      
    }
    
    public function allocateAmbassador($ambassador){
        
        DB::transaction(function () use($ambassador) {
            $exit=false;
            while (! $exit ) {
                // Check for High Priority Requests
                $result = Sign_up::selectHighPriority($ambassador['leader_gender'],$ambassador['gender']);
                if ($result->count() == 0){ 
                 // Check for SpecialCare
                 $result = Sign_up::selectSpecialCare($ambassador['leader_gender'],$ambassador['gender']);
                 if ($result->count() == 0){
                     //Check New Teams
                     $result = Sign_up::selectTeam($ambassador['leader_gender'],$ambassador['gender']);
                     if ($result->count() == 0){
                         //Check Teams With Less Than 12 Members
                         $result=Sign_up::selectTeam_between($ambassador['leader_gender'],$ambassador['gender'],"1","12");
                         if ($result->count() == 0){
                              //Check Teams With Less More 12 Members
                              $result=Sign_up::selectTeam($ambassador['leader_gender'],$ambassador['gender'],">","12");
                              if ($result->count() == 0){
                                // print_r($ambassador);
                                 $ambassadorWithoutLeader = User::create($ambassador);
                                 $exit=true;
                                 echo $this->jsonResponseWithoutMessage("Register Successfully --Without Leader", 'data', 200);
                             }
                             else{
                                 $exit =  $this->insert_ambassador($ambassador,$result);
                                 if($exit == true){
                                     echo $this->jsonResponseWithoutMessage("Register Successfully -- Teams With More Than 12 Members", 'data', 200);
                                 }
                                 else{
                                     continue;
                                 }
                             }
                         }//end if Teams With Less Than 12 Members
                         else{
                             $exit =  $this->insert_ambassador($ambassador,$result);
                             if($exit == true){
                                 echo $this->jsonResponseWithoutMessage("Register Successfully -- Teams With Less Than 12 Members", 'data', 200);
                             }
                             else{
                                 continue;
                             }
                         }//end else Teams With Less Than 12 Members
                     }//end if Check New Teams
                     else{
                         $exit =  $this->insert_ambassador($ambassador,$result);
                         if($exit == true){
                             echo $this->jsonResponseWithoutMessage("Register Successfully -- New Teams", 'data', 200);
                         }
                         else{
                             continue;
                         }
                     }//end if Check New Teams
                 }//end if Check for SpecialCare
                 else{
                     $exit =  $this->insert_ambassador($ambassador,$result);
                     if($exit == true){
                         echo $this->jsonResponseWithoutMessage("Register Successfully -- SpecialCare", 'data', 200);
                     }
                     else{
                         continue;
                     }
                 }//end else Check for SpecialCare
                }//end if Check for High Priority Requests
                else{
                    $exit =  $this->insert_ambassador($ambassador,$result);
                    if($exit == true){
                        echo $this->jsonResponseWithoutMessage("Register Successfully -- High Priority", 'data', 200);
                    }
                    else{
                     continue;
                    }
                 
                }//end else Check for High Priority Requests
          
            }//while     
        });
    }
    public function insert_ambassador($ambassador,$results){
        foreach($results as $result){
            $ambassador['request_id'] =$result->id;
            $countRequests=Sign_up::countRequests($result->id);
            if ($result->members_num > $countRequests){
            $user =User::create($ambassador);
            $user->assignRole($ambassador['user_type']);
            $countRequest = $countRequests + 1;
            if ($result->members_num <= $countRequest) {
                Sign_up::updateRequest($result->id);
                $msg = "You request is done";
                (new NotificationController)->sendNotification($result->leader_id , $msg);
            }
            $msg = "You have new user to your team";
            (new NotificationController)->sendNotification($result->leader_id , $msg);
            return true;
            }
            else{
                Sign_up::updateRequest($result->id);    
                $msg = "You request is done";
                (new NotificationController)->sendNotification($result->leader_id , $msg);           
                return false;
            }
        }
        
      }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return $this->jsonResponseWithoutMessage('You are Logged Out Successfully', 'data', 200);
    }
}
