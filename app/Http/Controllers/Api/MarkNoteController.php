<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Traits\ResponseJson;
use App\Models\MarkNote;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class MarkNoteController extends Controller
{
    use ResponseJson;

    /**
     * Display the specified mark note.
     *
     * This function retrieves a specific mark note based on the provided mark ID.
     * It ensures that the note belongs to the currently authenticated user.
     * If the note is found, it returns a JSON response with the note data and a 200 HTTP status code.
     * If the note is not found, it throws a NotFound exception.
     *
     * @param int $mark_id The ID of the mark note to retrieve.
     * @return \Illuminate\Http\JsonResponse JSON response containing the mark note data.
     * @throws NotFoundException If the mark note is not found.
     */
    public function show($mark_id)
    {
        $mark_note = MarkNote::with('mark.week')
                              ->where('from_id',Auth::id())
                              ->find($mark_id);
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
            'status' => 'required|int',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $request['mark_id'] = $request->mark_id;
        $request['from_id'] = Auth::id();
        $request['body'] = $request->body;
        $request['status'] = $request->status;

        MarkNote::create($request->all());

        return $this->jsonResponseWithoutMessage("MarkNote Created Successfully", 'data', 200);
    }


   
}
