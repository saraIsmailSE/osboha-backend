<?php


namespace App\Http\Controllers\Api\Eligible;

use App\Models\EligibleGeneralInformations;
use App\Models\EligibleQuestion;
use App\Models\EligibleThesis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\EligibleUserBook;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\ResponseJson;


class EligibleUserController extends Controller
{



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
    return $this->jsonResponseWithoutMessage($users, 'data', 200);
  }



  public function deactivate(Request $request)
  {
    $input = $request->all();

    $validator = Validator::make($input, [
      "id" => "required",
      "rejectNote" => "required",

    ]);
    if ($validator->fails()) {
      return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
    }
    $user = User::where('id', $request->id)->update(['allowed_to_eligible' => 2]);
    $userToNotify = User::find($request->id);
    $userToNotify->notify(new \App\Notifications\RejectUserEmail($request->rejectNote));
    $this->deleteTempMedia($request->id);

    $result = $user;

    if ($result == 0) {
      return $this->jsonResponseWithoutMessage('User does not exist', 'data', 404);
    }
    return $this->sendResponse($result, 'User deleted Successfully!');
  }

  public function listUnAllowedToEligible()
  {
    try {
      $users = User::with('roles')->where('allowed_to_eligible', 0)->whereHas(
        'roles',
        function ($q) {
          $q->where('name', 'user');
        }
      )->get();
      return $this->jsonResponseWithoutMessage($users, 'data', 200);
    } catch (\Error $e) {
      return $this->jsonResponseWithoutMessage('All Users Have Been Accepted', $e, 200);
    }
  }

  public function activeUser($id)
  {
    $user = User::find($id);
    try {
      $user->update(['allowed_to_eligible' => 1]);
    } catch (\Error $e) {
      return $this->jsonResponseWithoutMessage('User does not exist', $e, 200);
    }
    return $this->jsonResponseWithoutMessage($user, 'data', 200);
  }
de

  public function getUserStatistics()
  {
    $id = Auth::id();
    $thesises =  EligibleThesis::thesisStatisticsForUser($id);
    $qestions =  EligibleQuestion::questionsStatisticsForUser($id);
    $generalInformations = EligibleGeneralInformations::generalInformationsStatisticsForUser($id);
    $certificates =   EligibleUserBook::join('certificates', 'user_book.id', '=', 'certificates.user_book_id')->where('user_id', $id)->count();

    $response = [
      "thesises" => $thesises,
      "questions" => $qestions,
      "general_informations" => $generalInformations,
      "certificates" => $certificates,

    ];
    return $this->sendResponse($response, 'Statistics');
  }
}
