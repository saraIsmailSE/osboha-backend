<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookType;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\BookTypeResource;
use App\Models\ContactsWithWithdrawn;
use App\Models\User;

class ContactsWithWithdrawnController extends Controller
{
    use ResponseJson;

    public function sendEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_body' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $user = User::find($request->user_id);
        $user->notify((new \App\Notifications\ContactsWithWithdrawnAmbassador($request->email_body))->delay(now()->addMinutes(2)));
        return $this->jsonResponseWithoutMessage("تم الارسال", 'data', 200);
    }

    public function updateContactStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'contact' => 'required',
            'ambassador_id' => 'required',
            'return' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $contactRecored = ContactsWithWithdrawn::create(
            [
                'contact' => $request->contact,
                'ambassador_id' => $request->ambassador_id,
                'return' => $request->return,
                'reviewer_id' => Auth::id(),
            ]
        );

        $contactRecored = ContactsWithWithdrawn::find($contactRecored->id);

        return $this->jsonResponseWithoutMessage($contactRecored, 'data', 200);
    }

    public function showByUserID($user_id)
    {
        $contactRecored = ContactsWithWithdrawn::where('ambassador_id', $user_id)->latest()->first();
        if ($contactRecored) {
            return $this->jsonResponseWithoutMessage($contactRecored, 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage(null, 'data', 200);
        }
    }
}
