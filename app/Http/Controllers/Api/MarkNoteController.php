<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\NotFound;
use App\Models\Mark;
use App\Traits\ResponseJson;
use App\Models\MarkNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;
use App\Traits\PathTrait;


class MarkNoteController extends Controller
{
    use ResponseJson, PathTrait;

    /**
     * Function to show MarkNote details.
     *
     * @param int $mark_id The ID of the mark.
     * @return \Illuminate\Http\JsonResponse The response containing MarkNote details.
     * @throws NotFound If no MarkNote is found for the given mark_id.
     */

    public function getNotes($mark_id)
    {
        $mark_note = MarkNote::with('from')->where('mark_id', $mark_id)->get();
        return $this->jsonResponseWithoutMessage($mark_note, 'data', 200);
    }

    /**
     * Create a new mark note.
     *
     * This function handles the creation of a new mark note. It validates the incoming request data,
     * ensuring that the required fields are present and correctly formatted. If validation fails,
     * it returns a JSON response with the validation errors and a 500 HTTP status code.
     * If validation succeeds, it creates a new mark note with the provided data and returns
     * a JSON response indicating success with a 200 HTTP status code.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the data for the new mark note.
     * @return \Illuminate\Http\JsonResponse JSON response indicating the result of the operation.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required',
            'mark_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        try {
            $note = MarkNote::create([
                'mark_id' => $request->mark_id,
                'from_id' => Auth::id(),
                'body' => $request->body
            ]);

            //notifiy user [owner of the mark]
            $mark = Mark::find($request->mark_id);
            $msg = "لديك ملاحظة جديدة على علامتك ✨.";

            (new NotificationController)->sendNotification($mark->user_id, $msg, MARKS, $this->getMarkPath($mark->user_id, $mark->week_id));

            return $this->jsonResponseWithoutMessage($note, 'data', 200);
        } catch (Throwable $e) {
            report($e);
            return $e;
        }
    }
}
