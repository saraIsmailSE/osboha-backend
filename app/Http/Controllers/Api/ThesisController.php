<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\ThesisResource;
use App\Models\Comment;
use App\Models\Mark;
use App\Models\Thesis;
use App\Models\ThesisType;
use App\Models\Week;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\This;

class ThesisController extends Controller
{
    use ResponseJson;

    ##########ASMAA##########
    public function __construct()
    {
        define('MAX_PARTS', 5);
        define('MAX_SCREENSHOTS', 5);
        define('MAX_AYAT', 5);
        define('READING_MARK', 10);
        define('THESIS_MARK', 8);
        define('COMPLETE_THESIS_LENGTH', 420);
        define('PART_PAGES', 6);
        define('RAMADAN_PART_PAGES', 3);
        define('MIN_VALID_REMAINING', 3);
        define('INCREMENT_VALUE', 1);
        define('FULL_MARK_OUT_OF_90', 90);
        define('FULL_MARK_OUT_OF_100', 100);
        define('SUPPORT_MARK', 10);
        define('NORMAL_THESIS_TYPE', 'normal');
        define('RAMADAN_THESIS_TYPE', 'ramadan');
        define('TAFSEER_THESIS_TYPE', 'tafseer');
    }

    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'total_pages' => 'required|numeric',
                'total_screenshots' => 'required_without:max_length|numeric',
                'thesis_type_id' => 'required', //normal - ramadan - young - kids ...
                'max_length' => 'required_without:total_screenshots|numeric',
                'comment_id' => 'required',
                'book_id' => 'required',
                'mark_id' => 'required',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //get thesis type
        $thesis_type = ThesisType::find($request->thesis_type_id, ['type']);

        $week_id = Week::all('id')->last()->id;

        $mark_record = Mark::where('id', $request->mark_id)
            ->where('user_id', Auth::id())
            ->where('week_id', $week_id)
            ->first(['id', 'total_pages', 'total_thesis', 'total_screenshot', 'out_of_90', 'support']);

        if ($mark_record) {
            $max_length = ($request->has('max_length') ? $request->max_length : 0);
            $total_thesis = ($request->has('max_length') ? ($request['max_length'] > 0 ? INCREMENT_VALUE : 0) : 0);
            $total_screenshots = ($request->has('total_screenshots') ? $request->total_screenshots : 0);
            $thesis_mark = 0;

            $thesis_data_to_insert = array(
                'comment_id'        => $request->comment_id,
                'book_id'           => $request->book_id,
                'mark_id'           => $request->mark_id,
                'user_id'           => Auth::id(),
                'thesis_type_id'    => $request->thesis_type_id,
                'total_pages'       => $request->total_pages,
                'max_length'        => $max_length,
                'total_screenshots' => $total_screenshots,
            );

            $mark_data_to_update = array(
                'total_pages'      => $mark_record->total_pages + $request->total_pages,
                'total_thesis'     => $mark_record->total_thesis + $total_thesis,
                'total_screenshot' => $mark_record->total_screenshot + $total_screenshots,
            );

            $mark_out_of_90 = $mark_record->out_of_90;

            if (strtolower($thesis_type->type) === NORMAL_THESIS_TYPE) { //calculate mark for normal thesis or not completed ramadan/tafseer thesis                    
                $thesis_mark = $this->calculate_mark_for_normal_thesis(
                    $request->total_pages,
                    $max_length,
                    $total_screenshots,
                );
            } else if (
                strtolower($thesis_type->type) === RAMADAN_THESIS_TYPE ||
                strtolower($thesis_type->type) === TAFSEER_THESIS_TYPE
            ) { ///calculate mark for ramadan or tafseer thesis             

                $thesis_mark = $this->calculate_mark_for_ramadan_thesis(
                    $request->total_pages,
                    $max_length,
                    $total_screenshots,
                    (strtolower($thesis_type->type) === RAMADAN_THESIS_TYPE ? RAMADAN_THESIS_TYPE : TAFSEER_THESIS_TYPE)
                );
            }
            $mark_out_of_90 += $thesis_mark;

            if ($mark_out_of_90 > FULL_MARK_OUT_OF_90) {
                $mark_out_of_90 = FULL_MARK_OUT_OF_90;
            }

            $mark_out_of_100 = $mark_out_of_90;

            if ($mark_record->support == SUPPORT_MARK && $mark_out_of_100 > 0) {
                $mark_out_of_100 += SUPPORT_MARK;
            }

            $mark_data_to_update['out_of_90'] = $mark_out_of_90;
            $mark_data_to_update['out_of_100'] = $mark_out_of_100;

            $thesis = Thesis::create($thesis_data_to_insert);

            if ($thesis) {
                $mark_record->update($mark_data_to_update);
                return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis added successfully!');
            } else {
                return $this->jsonResponseWithoutMessage('Cannot add thesis', 'data', 500);
            }
        } else {
            throw new NotFound;
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), ['thesis_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $thesis = Thesis::find($request->thesis_id);

        if ($thesis) {
            return $this->jsonResponseWithoutMessage(new ThesisResource($thesis), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'total_pages' => 'required|numeric',
                'total_screenshots' => 'required_without:max_length|numeric',
                'max_length' => 'required_without:total_screenshots|numeric',
                'thesis_id' => 'required',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $thesis = Thesis::where('id', $request->thesis_id)->first(
            [
                'id', 'thesis_type_id', 'total_pages', 'mark_id',
                'max_length', 'total_screenshots'
            ]
        );

        if ($thesis) {
            $week_id = Week::all('id')->last()->id;

            $mark_record = Mark::where('id', $thesis->mark_id)
                ->where('user_id', Auth::id())
                ->where('week_id', $week_id)
                ->first(['id', 'total_pages', 'total_thesis', 'total_screenshot', 'out_of_90', 'support', 'week_id']);

            if ($mark_record) {
                if ($week_id == $mark_record->week_id) {
                    //get thesis type
                    $thesis_type = ThesisType::find($thesis->thesis_type_id, ['type']);

                    $max_length = ($request->has('max_length') ? $request->max_length : 0);
                    $total_thesis = ($request->has('max_length') ? ($request['max_length'] > 0 ? INCREMENT_VALUE : 0) : 0);
                    $total_screenshots = ($request->has('total_screenshots') ? $request->total_screenshots : 0);

                    $thesis_mark = 0;
                    $old_thesis_mark = 0;

                    $thesis_data_to_update = array(
                        'total_pages'       => $request->total_pages,
                        'max_length'        => $max_length,
                        'total_screenshots' => $total_screenshots,
                    );

                    if (strtolower($thesis_type->type) === NORMAL_THESIS_TYPE) { //calculate mark for normal thesis                     
                        $thesis_mark = $this->calculate_mark_for_normal_thesis(
                            $request->total_pages,
                            $max_length,
                            $total_screenshots
                        );
                        //calculate the old mark to remove it from the total                        
                        $old_thesis_mark = $this->calculate_mark_for_normal_thesis(
                            $thesis->total_pages,
                            $thesis->max_length,
                            $thesis->total_screenshots
                        );
                    } else if (
                        strtolower($thesis_type->type) === RAMADAN_THESIS_TYPE ||
                        strtolower($thesis_type->type) === TAFSEER_THESIS_TYPE
                    ) { ///calculate mark for ramadan or tafseer thesis             
                        $thesis_mark = $this->calculate_mark_for_ramadan_thesis(
                            $request->total_pages,
                            $max_length,
                            $total_screenshots,
                            $thesis_type->type,
                        );

                        $old_thesis_mark = $this->calculate_mark_for_ramadan_thesis(
                            $thesis->total_pages,
                            $thesis->max_length,
                            $thesis->total_screenshots,
                            $thesis_type->type,
                        );
                    }
                    $mark_out_of_90 = $thesis_mark + $mark_record->out_of_90 - $old_thesis_mark;

                    if ($mark_out_of_90 > FULL_MARK_OUT_OF_90) {
                        $mark_out_of_90 = FULL_MARK_OUT_OF_90;
                    }

                    $mark_out_of_100 = $mark_out_of_90;

                    if ($mark_record->support == SUPPORT_MARK && $mark_out_of_100 > 0) {
                        $mark_out_of_100 += SUPPORT_MARK;
                    }

                    $mark_data_to_update = array(
                        'total_pages'      => $mark_record->total_pages - $thesis->total_pages + $request->total_pages,
                        'total_thesis'     => $mark_record->total_thesis - ($thesis->max_length > 0 ? INCREMENT_VALUE : 0) + $total_thesis,
                        'total_screenshot' => $mark_record->total_screenshot - $thesis->total_screenshots + $total_screenshots,
                        'out_of_90' => $mark_out_of_90,
                        'out_of_100' => $mark_out_of_100,
                    );


                    $thesis->update($thesis_data_to_update);

                    $mark_record->update($mark_data_to_update);

                    return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis updated successfully!');
                } else {
                    return $this->jsonResponseWithoutMessage('Cannot update thesis of previous weeks', 'data', 500);
                }
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotFound;
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'thesis_id' => 'required',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $thesis = Thesis::where('id', $request->thesis_id)->first(
            [
                'id', 'thesis_type_id', 'total_pages', 'mark_id',
                'max_length', 'total_screenshots', 'comment_id'
            ]
        );

        if ($thesis) {
            $comment = Comment::where('id', $thesis->comment_id)->first('id');

            $week_id = Week::all('id')->last()->id;

            $mark_record = Mark::where('id', $thesis->mark_id)
                ->where('user_id', Auth::id())
                ->where('week_id', $week_id)
                ->first(['id', 'total_pages', 'total_thesis', 'total_screenshot', 'out_of_90', 'support', 'week_id']);

            if ($mark_record) {
                if ($week_id == $mark_record->week_id) {
                    $thesis->delete();
                    $comment->delete();

                    $mark_out_of_90 = $this->calculate_mark_for_all_thesis($thesis->mark_id);

                    if ($mark_out_of_90 > FULL_MARK_OUT_OF_90) {
                        $mark_out_of_90 = FULL_MARK_OUT_OF_90;
                    }

                    $mark_out_of_100 = $mark_out_of_90;

                    if ($mark_record->support == SUPPORT_MARK && $mark_out_of_100 > 0) {
                        $mark_out_of_100 += SUPPORT_MARK;
                    }

                    $mark_data_to_update = array(
                        'total_pages'      => $mark_record->total_pages - $thesis->total_pages,
                        'total_thesis'     => $mark_record->total_thesis - ($thesis->max_length > 0 ? INCREMENT_VALUE : 0),
                        'total_screenshot' => $mark_record->total_screenshot - $thesis->total_screenshots,
                        'out_of_90' => $mark_out_of_90,
                        'out_of_100' => $mark_out_of_100,
                    );

                    $mark_record->update($mark_data_to_update);

                    return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis deleted successfully!');
                } else {
                    return $this->jsonResponseWithoutMessage('Cannot delete thesis of previous weeks', 'data', 500);
                }
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotFound;
        }
    }

    public function calculate_mark_for_all_thesis($mark_id)
    {
        $total_mark = 0;

        $thesises = Thesis::join('thesis_types', 'thesis_types.id', '=', 'theses.thesis_type_id')
            ->where('mark_id', $mark_id)->get([
                'theses.id', 'thesis_type_id', 'total_pages', 'mark_id',
                'max_length', 'total_screenshots', 'thesis_types.type'
            ]);

        foreach ($thesises as $thesis) {
            if ($thesis->type === NORMAL_THESIS_TYPE) {
                $total_mark += $this->calculate_mark_for_normal_thesis(
                    $thesis->total_pages,
                    $thesis->max_length,
                    $thesis->total_screenshots
                );
            } else if ($thesis->type === RAMADAN_THESIS_TYPE || $thesis->type === TAFSEER_THESIS_TYPE) {
                $total_mark += $this->calculate_mark_for_ramadan_thesis(
                    $thesis->total_pages,
                    $thesis->max_length,
                    $thesis->total_screenshots,
                    $thesis->type
                );
            }
        }

        return $total_mark;
    }

    public function calculate_mark_for_normal_thesis($total_pages, $max_length, $total_screenshots) //isNew used to tell if the thesis is new or updated
    {
        $mark = 0;
        $number_of_parts = (int) ($total_pages / PART_PAGES);
        $number_of_remaining_pages_out_of_part = $total_pages % PART_PAGES; //used if the parts less than 5 

        if ($number_of_parts > MAX_PARTS) { //if the parts exceeded the max number 
            $number_of_parts = MAX_PARTS;
        } else if (
            $number_of_parts < MAX_PARTS &&
            $number_of_remaining_pages_out_of_part >= MIN_VALID_REMAINING
        ) {
            $number_of_parts += INCREMENT_VALUE;
        }
        //reading mark    
        $mark = $number_of_parts * READING_MARK;

        if ($max_length > 0) {
            if ($max_length >= COMPLETE_THESIS_LENGTH) { //COMPLETE THESIS                           
                $mark += $number_of_parts * THESIS_MARK;
            } else { //INCOMPLETE THESIS
                $mark += THESIS_MARK;
            }
        } else if ($total_screenshots > 0) {
            $screenshots = $total_screenshots;
            if ($screenshots >= MAX_SCREENSHOTS) {
                $screenshots = MAX_SCREENSHOTS;
            }
            if ($screenshots > $number_of_parts) {
                $screenshots = $number_of_parts;
            }

            $mark += $screenshots * THESIS_MARK;
        }

        return $mark;
    }

    public function calculate_mark_for_ramadan_thesis($total_pages, $max_length, $total_screenshots, $thesis_type)
    {
        if ($max_length <= 0 && $total_screenshots <= 0) { //if no thesis -- it is considered as normal thesis
            return $this->calculate_mark_for_normal_thesis($total_pages, $max_length, $total_screenshots);
        }

        $mark = 0;

        if ($thesis_type === RAMADAN_THESIS_TYPE) {
            $number_of_parts = (int) ($total_pages / RAMADAN_PART_PAGES);
        }
        //tafseer thesis consedered based on the number of ayats
        else if ($thesis_type === TAFSEER_THESIS_TYPE) {
            $number_of_parts = $total_pages;
        }

        if ($number_of_parts > MAX_PARTS) { //if the parts exceeded the max number 
            $number_of_parts = MAX_PARTS;
        }

        //reading mark    
        $mark = $number_of_parts * READING_MARK;

        if ($max_length > 0) {
            if ($max_length >= COMPLETE_THESIS_LENGTH) { //COMPLETE THESIS                           
                $mark += $number_of_parts * THESIS_MARK;
            } else { //INCOMPLETE THESIS
                $mark += THESIS_MARK;
            }
        } else if ($total_screenshots > 0) {
            $screenshots = $total_screenshots;
            if ($screenshots >= MAX_SCREENSHOTS) {
                $screenshots = MAX_SCREENSHOTS;
            }
            if ($screenshots > $number_of_parts) {
                $screenshots = $number_of_parts;
            }
            $mark += $screenshots * THESIS_MARK;
        }

        return $mark;
    }

    public function list_book_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['book_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //select function to be added in order to reduce the data retrieved
        $thesis = Thesis::join('comments', 'comments.id', '=', 'theses.comment_id')
            ->leftJoin('media', 'comments.id', '=', 'media.comment_id')
            ->where('theses.book_id', $request->book_id)
            ->get();

        if ($thesis->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesis), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function list_user_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['user_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //select function to be added in order to reduce the data retrieved
        $thesis = Thesis::join('comments', 'comments.id', '=', 'theses.comment_id')
            ->leftJoin('media', 'comments.id', '=', 'media.comment_id')
            ->where('theses.user_id', $request->user_id)
            ->get();

        if ($thesis->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesis), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function list_week_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['week_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //select function to be added in order to reduce the data retrieved
        $thesis = Thesis::select('theses.*')
            ->join('marks', 'marks.id', '=', 'theses.mark_id')
            ->join('weeks', 'weeks.id', '=', 'marks.week_id')
            ->join('comments', 'comments.id', '=', 'theses.comment_id')
            ->leftJoin('media', 'comments.id', '=', 'media.comment_id')
            ->where('marks.week_id', $request->week_id)
            ->get();

        if ($thesis->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesis), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}