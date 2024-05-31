<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Traits\ResponseJson;
use App\Models\MarkNote;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Throwable;


class MarkNoteController extends Controller
{
    use ResponseJson;

    /**
     * Function to show MarkNote details.
     *
     * @param int $mark_id The ID of the mark.
     * @return \Illuminate\Http\JsonResponse The response containing MarkNote details.
     * @throws NotFound If no MarkNote is found for the given mark_id.
     */

    public function show($mark_id)
    {
        $mark_note = MarkNote::with('mark.week')
                              ->where('mark_id',$mark_id)
                              ->get();
        if ($mark_note) {
            return $this->jsonResponseWithoutMessage($mark_note, 'data', 200);
        } else {
            throw new NotFound;
        }
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
            'mark_id' => 'required|int',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        try {
            $request['mark_id'] = $request->mark_id;
            $request['from_id'] = Auth::id();
            $request['body'] = $request->body;
            $mark_note = MarkNote::create($request->all());
            return $this->jsonResponseWithoutMessage($mark_note, 'data', 200);
            
        } catch (Throwable $e) {
            report($e);
            return $e;
        }

    }


   
}
