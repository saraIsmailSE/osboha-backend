<?php


namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\EligibleUserBook;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use App\Traits\Eligible_MediaTraits;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class EligibleUserController extends Controller
{

  use Eligible_MediaTraits;


  /**
   * Get all users
   * @return users;
   */

  public function index()
  {

    $users = User::all();
    return $this->sendResponse($users, "Users");
  }

  /**
   * Get users by name.
   * 
   * @param  user name
   * @return users;
   */

  public function searchByName($name)
  {
    $users = User::where('name', 'like', '%' . $name . '%')
      ->get();
    return $this->sendResponse($users, "Users");
  }

  public function store(Request $request)
  {

    $validator = Validator::make($request->all(), [
      "name" => "required",
      "email" => "required|email",
      "password" => 'required',
      'role' => 'required',
      "image" => "required|image|mimes:png,jpg,jpeg,gif,svg|max:2048",
    ]);

    if ($validator->fails()) {
      return $this->sendError($validator->errors());
    }
    $input = $request->all();
    $role = $this->GetRole($input['role']);
    $input['password'] = Hash::make($request->password);
    try {

      $user = User::create($input);
      $user->assignRole($role);
      $this->createUserPhoto($request->file('image'), $user);
      event(new Registered($user));
    } catch (\Illuminate\Database\QueryException $e) {
      $errorCode = $e->errorInfo[1];
      if ($errorCode == 1062) {
        return $this->sendError('User already exist');
      }
    }
    return $this->sendResponse($user, "User created");
  }


  public function updateInfo(Request $request)
  {
    try {
      $validator = Validator::make($request->all(), [
        "image" => "required|image|mimes:png,jpg,jpeg,gif,svg",
      ]);

      if ($validator->fails()) {
        return $this->sendError($validator->errors());
      }


      $user = User::where('id', Auth::id())->update(['name' => $request->name, 'is_active' => 0]);
      $user = User::where('id', Auth::id())->first();
      $userImage = $this->createMedia($request->file('image'));
      $user->picture = $userImage;
      $user->save();
    } catch (\Error $e) {
      Log::error($e);
    }
  }


  public function updateName(Request $request)
  {
    try {
      $validator = Validator::make($request->all(), [
        "name" => "required",
        "id" => "required",
      ]);

      if ($validator->fails()) {
        return $this->sendError($validator->errors());
      }


      $user = User::where('id', $request->id)->update(['name' => $request->name]);
      return $this->sendResponse($user, "تم التعديل");
    } catch (\Error $e) {
      Log::error($e);
    }
  }



  private function GetRole($role)
  {
    $role = Role::where('name', $role)->first();
    if (is_null($role)) {

      return $this->sendError('Role does not exist');
    }

    return $role;
  }

  public function show($id)
  {
    $user = User::find($id);
    if (is_null($user)) {

      return $this->sendError('User does not exist');
    }
    return $this->sendResponse($user, "User");
  }


  public function update(Request $request,  $id)
  {
    $input = $request->all();
    $validator = Validator::make($input, [
      "name" => "required",
      "email" => "required|email",
      "password" => 'required',
      "role" => "required"
    ]);
    if ($validator->fails()) {
      return $this->sendError('Validation error', $validator->errors());
    }

    $user = User::find($id);
    $input['password'] = Hash::make($request->password);
    $updateParam = [
      "name" => $input['name'],
      "email" => $input['email'],
      "password" => $input['password'],
      "role" => $input['role'],
    ];
    try {
      $user->update($updateParam);
    } catch (\Error $e) {
      return $this->jsonResponseWithoutMessage('User does not exist',$e, 200);

    }
    return $this->sendResponse($user, 'User updated Successfully!');
  }

  public function deactivate(Request $request)
  {
    $input = $request->all();

    $validator = Validator::make($input, [
      "id" => "required",
      "rejectNote" => "required",

    ]);
    if ($validator->fails()) {
      return $this->sendError('Validation error', $validator->errors());
    }
    $user = User::where('id', $request->id)->update(['is_active' => 2]);
    $userToNotify = User::find($request->id);
    $userToNotify->notify(new \App\Notifications\RejectUserEmail($request->rejectNote));
    $this->deleteTempMedia($request->id);

    $result = $user;

    if ($result == 0) {

      return $this->sendError('User does not exist');
    }
    return $this->sendResponse($result, 'User deleted Successfully!');
  }





  public function listUnactiveUser()
  {
    try {
      $users = User::with('roles')->where('is_active', 0)->whereHas(
        'roles',
        function ($q) {
          $q->where('name', 'user');
        }
      )->get();
      return $this->sendResponse($users, 'All Un Accepted Users!');
    } catch (\Error $e) {
      return $this->jsonResponseWithoutMessage('All Users Have Been Accepted',$e, 200);

    }
  }

  public function listUnactiveReviwers()
  {
    try {
      $users = User::with('roles')->where('is_active', 0)->whereHas(
        'roles',
        function ($q) {
          $q->where('name', 'reviewer')->orWhere('name', 'auditor');
        }
      )->get();

      return $this->sendResponse($users, 'All Un Accepted Reviewers And Auditors!');
    } catch (\Error $e) {
      return $this->jsonResponseWithoutMessage('All Reviewers And Auditors Have Been Accepted',$e, 200);

    }
  }

  public function activeUser(Request $request, $id)
  {
    $user = User::find($id);
    if (!is_null($user->picture)) {
      $this->deleteTempMedia($request->id);
    }
    try {
      $user->update(['is_active' => true, 'picture' => null]);
    } catch (\Error $e) {
      return $this->jsonResponseWithoutMessage('User does not exist',$e, 200);

    }

    return $this->sendResponse($user, 'user activated!');
  }




  public function registerAdmin(Request $request)
  {

    $validator = Validator::make($request->all(), [
      "name" => "required",
      "email" => "required|email",
      "password" => 'required',
      'role' => 'required',
      'fb_name' => 'required'
    ]);

    if ($validator->fails()) {
      return $this->sendError($validator->errors());
    }
    $input = $request->all();
    $role  = Role::where('name', $input['role'])->first();
    $input['password'] = Hash::make($request->password);

    try {

      $user = User::create($input);
      $user->assignRole($role);
      event(new Registered($user));
    } catch (\Illuminate\Database\QueryException $e) {
      $errorCode = $e->errorInfo[1];
      if ($errorCode == 1062) {
        return $this->sendError('User already exist');
      }
    }
  }





  public function image(Request $request)
  {

    if (isset($_GET['fileName'])) {
      $path = public_path() . '/asset/images/temMedia/' . $_GET['fileName'];
      return response()->download($path, $_GET['fileName']);
    } else {
      return $this->sendError('file nout found');
    }
  }




  public function getUserStatistics()
  {
    $id = Auth::id();
    $thesises =  ThesisController::thesisStatisticsForUser($id);
    $qestions =  QuestionController::questionsStatisticsForUser($id);
    $generalInformations = GeneralInformationsController::generalInformationsStatisticsForUser($id);
    $certificates =   UserBook::join('certificates', 'user_book.id', '=', 'certificates.user_book_id')->where('user_id', $id)->count();

    $response = [
      "thesises" => $thesises,
      "questions" => $qestions,
      "general_informations" => $generalInformations,
      "certificates" => $certificates,

    ];
    return $this->sendResponse($response, 'Statistics');
  }
}
