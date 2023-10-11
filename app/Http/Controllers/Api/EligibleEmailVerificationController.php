<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use App\Http\EmailVerificationRequest;
use App\Models\User;
class EligibleEmailVerificationController extends Controller
{

    public function sendVerificationEmail(Request $request)
    {

        if ($request->user()->hasVerifiedEmail()) {
            return [
                'message' => 'Already Verified'
            ];
        }

        $request->user()->sendEmailVerificationNotification();

        return ['status' => 'verification-link-sent'];
    }

    public function verify(EmailVerificationRequest $request,$id,$hash)
    {
        $user = User::find($id);


        if (! hash_equals((string) $hash,
                          sha1($user->getEmailForVerification()))) {
            return false;
        }

        if ($user->hasVerifiedEmail()) {
            return view('confairnEmail');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return view('confairnEmail');
    }
}
