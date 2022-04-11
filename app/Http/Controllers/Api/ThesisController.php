<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\ThesisResource;
use App\Models\Comment;
use App\Models\Mark;
use App\Models\Thesis;
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
        define('READING_MARK', 10);
        define('THESIS_MARK', 8);
        define('COMPLETE_THESIS_LENGTH', 420);
        define('PART_PAGES', 6);
        define('MIN_VALID_REMAINING', 3);
        define('INCREMENT_VALUE', 1);
        define('FULL_MARK_OUT_OF_90', 90);
        define('FULL_MARK_OUT_OF_100', 100);
        define('SUPPORT_MARK', 10);
        define('NORMAL_THESIS_TYPE', 'normal');
        define('RAMADAN_THESIS_TYPE', 'ramadan');
    }

    // public function index()
    // {
    //     $thesises = Thesis::all();

    //     if($thesises->isNotEmpty()){
    //         return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesises), 'data', 200);
    //     }else{
    //         throw new NotFound;
    //     }
    // }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), 
        [
          'total_pages' => 'required|numeric',
          'total_screenshots' => 'required_without:max_length|numeric',
          'type' => 'required', //normal - ramadan - young - kids ...
          'max_length' => 'required_without:total_screenshots|numeric',
          'comment_id' => 'required',
          'book_id' => 'required',          
          'mark_id' => 'required',
        ]);

        if($validator->fails())
        {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
                
        if($request->type === NORMAL_THESIS_TYPE)
        {//calculate mark for normal thesis                                                  
            return $this->calculate_mark_for_normal_thesis($request);                                   
        }
        else if
        ($request->type === RAMADAN_THESIS_TYPE)
        {///calculate mark for ramadan thesis             

        }    
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), ['thesis_id' => 'required']);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $thesis = Thesis::find($request->thesis_id);

        if($thesis->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(new ThesisResource($thesis), 'data', 200);
        }else{
            throw new NotFound;
        }
    }

    public function calculate_mark_for_normal_thesis($request)
    {   
        $user_id = Auth::id();
             
        $current_date = date('Y-m-d');

        $week_id = Week::where('date', $current_date)->first()->id;

        $mark_record = Mark::where('id', $request->mark_id)
                           ->where('user_id', $user_id)
                           ->where('week_id', $week_id)
                           ->first();                              

        if($mark_record)
        {
            //data to be inserted
            $thesis_data_to_insert = array
            (
                'comment_id' => $request->comment_id,
                'book_id' =>$request->book_id,
                'mark_id' => $request->mark_id,
                'user_id' => $user_id,
                'type' => $request->type,  
                'total_pages' => $request->total_pages,                              
            );

            $mark_data_to_update = array
            (
                'total_pages' => $mark_record->total_pages + $request->total_pages,                 
            );

            if($mark_record->out_of_90 == FULL_MARK_OUT_OF_90)
            {//if the mark is full --> add thesis only without updating the mark            
                if($request->has('max_length')  && $request->max_length > 0)
                {
                    $thesis_data_to_insert['max_length'] = $request->max_length;                
                    $mark_data_to_update['total_thesis'] = $mark_record->total_thesis + INCREMENT_VALUE;
                }   
                else if($request->has('total_screenshots') && $request->total_screenshots > 0)
                {
                    $thesis_data_to_insert['total_screenshots'] = $request->total_screenshots;                    
                    $mark_data_to_update['total_screenshot'] = $mark_record->total_screenshot + $request->total_screenshot;
                }                
                    
                $thesis = Thesis::create($thesis_data_to_insert);

                if($thesis)
                {   
                    $mark_record->update($mark_data_to_update); 
                    return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis added successfully!');
                }
                else
                {
                    return $this->jsonResponseWithoutMessage('Cannot add thesis', 'data', 500);             
                }
            }
            else
            {                                       
                $mark_out_of_90 = 0;
                $mark_out_of_100 = 0;
                $mark = 0;
                $number_of_parts = (int) ($request->total_pages / PART_PAGES);                    
                $number_of_remaining_pages_out_of_part = $request->total_pages % PART_PAGES; //used if the parts less than 5 
                
                if($number_of_parts > MAX_PARTS) 
                {//if the parts exceeded the max number 
                    $number_of_parts = MAX_PARTS;
                }
                else if($number_of_parts < MAX_PARTS && 
                        $number_of_remaining_pages_out_of_part >= MIN_VALID_REMAINING)
                {
                    $number_of_parts += INCREMENT_VALUE;
                }                                       
                //reading mark    
                $mark = $number_of_parts * READING_MARK;

                if($request->has('max_length')  && $request->max_length > 0)
                {                   
                    if($request->max_length >= COMPLETE_THESIS_LENGTH) 
                    { //COMPLETE THESIS                           
                        $mark += $number_of_parts * THESIS_MARK; 
                    }
                    else
                    { //INCOMPLETE THESIS
                        $mark += THESIS_MARK;
                    }                                                                                                                                                                                
                    $thesis_data_to_insert['max_length'] = $request->max_length;
                    // $thesis_data_to_insert['total_screenshots'] = 0;
                    $mark_data_to_update['total_thesis'] = $mark_record->total_thesis + INCREMENT_VALUE;
                }
                else if($request->has('total_screenshots') && $request->total_screenshots > 0)
                {
                    $screenshots = $request->total_screenshots;                
                    if($screenshots >= MAX_SCREENSHOTS)
                    {
                        $screenshots = MAX_SCREENSHOTS;                
                    }
        
                    if($screenshots > $number_of_parts)
                    {
                        $screenshots = $number_of_parts;
                    }
        
                    $mark += $screenshots * THESIS_MARK;
                    $thesis_data_to_insert['total_screenshots'] = $request->total_screenshots;    
                    // $thesis_data_to_insert['max_length'] = 0;    
                    $mark_data_to_update['total_screenshot'] = $mark_record->total_screenshot + $request->total_screenshots;    
                }        
                else if($request->has('max_length') && $request->max_length == 0)
                {               
                    $thesis_data_to_insert['max_length'] = $request->max_length;                        
                    // $thesis_data_to_insert['total_screenshot'] = 0;    
                }
                else if($request->has('total_screenshots') && $request->total_screenshots == 0)
                {
                    $thesis_data_to_insert['total_screenshots'] = $request->total_screenshots;                                                    
                    // $thesis_data_to_insert['max_length'] = 0;    
                }

                $mark_out_of_90 = $mark + $mark_record->out_of_90;
                
                if($mark_out_of_90 > FULL_MARK_OUT_OF_90)
                {
                    $mark_out_of_90 = FULL_MARK_OUT_OF_90;
                }
                
                $mark_out_of_100 = $mark_out_of_90;

                if($mark_record->support == 1)
                {
                    $mark_out_of_100 += SUPPORT_MARK;
                }

                $mark_data_to_update['out_of_90'] = $mark_out_of_90;
                $mark_data_to_update['out_of_100'] = $mark_out_of_100; 
                
                $thesis = Thesis::create($thesis_data_to_insert);

                if($thesis)
                {
                    $mark_record->update($mark_data_to_update);

                    return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis added successfully');
                }
                else
                {
                    return $this->jsonResponseWithoutMessage('Cannot add thesis', 'data', 500);
                }                
            } 
        }
        else
        {//when mark not found
            throw new NotFound;
        }       
    }

    public function list_book_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['book_id' => 'required']);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //select function to be added in order to reduce the data retrieved
        $thesis = Thesis::join('comments', 'comments.id', '=', 'theses.comment_id')
                        ->leftJoin('media', 'comments.id', '=', 'media.comment_id')
                        ->where('theses.book_id', $request->book_id)
                        ->get();

        if($thesis->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesis), 'data', 200);
        }else{
            throw new NotFound;
        } 
    }

    public function list_user_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['user_id' => 'required']);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //select function to be added in order to reduce the data retrieved
        $thesis = Thesis::join('comments', 'comments.id', '=', 'theses.comment_id')
                        ->leftJoin('media', 'comments.id', '=', 'media.comment_id')
                        ->where('theses.user_id', $request->user_id)
                        ->get();  

        if($thesis->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesis), 'data', 200);
        }else{
            throw new NotFound;
        } 
    }    

    public function list_week_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['week_id' => 'required']);

        if($validator->fails()){
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

        if($thesis->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesis), 'data', 200);
        }else{
            throw new NotFound;
        } 
    }        
}
