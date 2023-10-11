<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\EligibleGeneralInformations;
use App\Models\EligiblePhotos;
use App\Models\EligibleQuestion;
use App\Models\EligibleThesis;
use App\Models\User;
use App\Models\EligibleUserBook;
use Illuminate\Support\Facades\Validator;
use App\Traits\Eligible_MediaTraits;
use GuzzleHttp\Psr7\Query;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;



class EligibleGeneralThesisController extends Controller
{

    use Eligible_MediaTraits;

    public function index()
    {

        $thesis = EligibleGeneralThesis::all();
        return $this->jsonResponseWithoutMessage('Thesises',$thesis, 200);

    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "thesis_text" => "required",
            "ending_page" => 'required',
            "starting_page" => 'required',
            "eligible_user_book_id" => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $input = $request->all();

        try {
            $newthesis = EligibleGeneralThesis::create($input);

        } catch (\Illuminate\Database\QueryException $e) {
            return $this->sendError($e, 'User Book does not exist');
        }
        $thesis = EligibleGeneralThesis::find($newthesis->id);
        return $this->jsonResponseWithoutMessage('Thesis created',$thesis, 200);

    }


    public function show($id)
    {
        $thesis = EligibleGeneralThesis::where('id',$id)->with('user_book.book')->first();

        if (is_null($thesis)) {

            return $this->sendError('Thesis does not exist');
        }
        return $this->jsonResponseWithoutMessage('Thesis',$thesis, 200);

    }

    public function update(Request $request,  $id)
    {
        $validator = Validator::make($request->all(), [
            "text" => "required",
            "ending_page" => 'required',
            "starting_page" => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        try {
            $thesis = EligibleGeneralThesis::find($id);
            if (Auth::id() == $thesis->user_book->user_id) {

                $thesis->thesis_text=$request->text;
                $thesis->ending_page=$request->ending_page;
                $thesis->starting_page=$request->starting_page;
                $thesis->save();



            }
        } catch (\Error $e) {
            return $this->sendError('Thesis does not exist');
        }
        return $this->jsonResponseWithoutMessage('Thesis updated Successfully!',$thesis, 200);

    }

    public function destroy($id)
    {

        $result = EligibleGeneralThesis::destroy($id);

        if ($result == 0) {

            return $this->sendError('thesis not found!');
        }
        return $this->jsonResponseWithoutMessage('Thesis deleted Successfully!',$thesis, 200);

    }



    public function addDegree(Request $request,  $id)
    {
        $validator = Validator::make($request->all(), [
            'reviews' => 'required',
            'degree' => 'required',
            'auditor_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $thesis = EligibleGeneralThesis::find($id);
        $thesis->reviews = $request->reviews;
        $thesis->degree = $request->degree;
        $thesis->auditor_id = $request->auditor_id;
        $thesis->status = 'audited';


        try {
            $thesis->save();
            // Stage Up
            $auditedTheses = EligibleGeneralThesis::where('user_book_id', $thesis->user_book_id)->where('status', 'audited')->count();
            $auditedGeneralInfo = GeneralInformations::where('user_book_id', $thesis->user_book_id)->where('status', 'audited')->count();
            $auditedQuestions = Question::where('user_book_id', $thesis->user_book_id)->where('status', 'audited')->count();
            if ($auditedTheses >= 8 && $auditedQuestions >= 5 && $auditedGeneralInfo) {
                $userBook = UserBook::where('id', $thesis->user_book_id)->update(['status' => 'audited']);
            }

        } catch (\Error $e) {
            return $this->sendError('Thesis does not exist');
        }
        return $this->jsonResponseWithoutMessage('Degree added Successfully!',$thesis, 200);

    }




    public function finalDegree($user_book_id)
    {
        $degrees = EligibleGeneralThesis::where("user_book_id", $user_book_id)->avg('degree');
        return $this->jsonResponseWithoutMessage('Final Degree!',$degrees, 200);

    }

    public function uploadPhoto(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [

            "image"  => 'required|image|mimes:png,jpg,jpeg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $thesis = EligibleGeneralThesis::find($id);
        if (is_null($thesis)) {
            return $this->sendError('Thesis does not exist');
        }
        if ($request->has('image')) {
            $this->createThesisMedia($request->file('image'), $thesis->id);
        }
        return $this->jsonResponseWithoutMessage('Photo uploaded Successfully!',$thesis, 200);

    }

    //ready to review

    public function reviewThesis($id)
    {
        try {
            $thesis = EligibleGeneralThesis::where('user_book_id', $id)->where(function ($query) {
                $query->where('status','retard')
                    ->orWhereNull('status');
            })->update(['status' => 'ready']);
            return $thesis;
        } catch (\Error $e) {
            return $this->sendError('Thesis does not exist');
        }
    }

    public function review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required_without:user_book_id',
            'user_book_id' => 'required_without:id',
            'status' => 'required',
            'reviewer_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            if($request->has('id')){
                $thesis = EligibleGeneralThesis::find($request->id);
                $thesis->status = $request->status;
                $thesis->reviewer_id = $request->reviewer_id;
                if ($request->has('reviews')) {
                    $thesis->reviews = $request->reviews;
                    $userBook=UserBook::find($thesis->user_book_id);
                    $user=User::find($userBook->user_id);
                    $userBook->status=$request->status;
                    $userBook->reviews=$request->reviews;
                    $userBook->save();
                    $user->notify(
                        (new \App\Notifications\RejectAchievement())->delay(now()->addMinutes(2))
                    );
                    }
                $thesis->save();
            }
            else if($request->has('user_book_id')){
                $thesis = EligibleGeneralThesis::where('user_book_id',$request->user_book_id)->where('status', 'accept')->update(['status'=>$request->status]);

            }
        } catch (\Error $e) {
            return $this->sendError('Thesis does not exist');
        }
    }

    public function getByStatus($status){
        $thesises =  EligibleGeneralThesis::with("user_book.user")->with("user_book.book")->where('status',$status)->orderBy('updated_at', 'asc')->groupBy('user_book_id')->get();
 
        return $this->jsonResponseWithoutMessage('Thesises',$thesis, 200);

    }

    public function getByUserBook($user_book_id,$status='')
    {
        if($status != ''){
            $response['thesises'] =  EligibleGeneralThesis::with("user_book.user")->with("user_book.book")->with('reviewer')->with('auditor')->where('user_book_id', $user_book_id)->where('status',$status)->get();
        }
        else{
            $response['thesises'] =  EligibleGeneralThesis::with("user_book.user")->with("user_book.book")->with('reviewer')->with('auditor')->where('user_book_id', $user_book_id)->get();
        }
        $response['acceptedThesises'] =  EligibleGeneralThesis::where('user_book_id', $user_book_id)->where('status','accept')->count();
        $response['userBook'] =  UserBook::find($user_book_id);
        return $this->jsonResponseWithoutMessage('Thesises',$response, 200);

    }

    public function getByBook($book_id)
    {
        $theses['user_book']= UserBook::where('user_id', Auth::id())->where('book_id', $book_id)->first();
        $theses['theses'] =  EligibleGeneralThesis::with('reviewer')->with('auditor')->where('user_book_id', $theses['user_book']->id)->orderBy('created_at')->get();
        return $this->jsonResponseWithoutMessage('Thesises',$theses, 200);

        
    }


    public function image(Request $request)
    {
        $path = $request->query('path', 'not found');
        if ($path === 'not found') {
            return $this->sendError('Path nout found');
        }
        $image = Storage::get($path);
       	
	 $exp = "/[.][a-z][a-z][a-z]/";
        if (is_null($image)) {
            return $this->sendError('Image not found');
        }

        preg_match($exp, $path, $matches);
        $extention = ltrim($matches[0], '.');

        return response($image, 200)->header('Content-Type', "image/$extention");
    }

    public static function thesisStatistics(){
        $thesisCount = EligibleGeneralThesis::count();
        $very_excellent =  EligibleGeneralThesis::where('degree' ,'>=',95)->where('degree','<',100)->count();
        $excellent = EligibleGeneralThesis::where('degree' ,'>',94.9)->where('degree','<',95)->count();
        $veryGood =  EligibleGeneralThesis::where('degree' ,'>',89.9)->where('degree','<',85)->count();
        $good = EligibleGeneralThesis::where('degree' ,'>',84.9)->where('degree','<',80)->count();
        $accebtable = EligibleGeneralThesis::where('degree' ,'>',79.9)->where('degree','<',70)->count();
        $rejected = EligibleGeneralThesis::where('status','rejected')->count();
        return [
            "total" => $thesisCount,
            "very_excellent" =>( $very_excellent / $thesisCount) * 100,
            "excellent" =>( $excellent / $thesisCount) * 100,
            "very_good" =>( $veryGood / $thesisCount) * 100,
            "good" =>( $good / $thesisCount) * 100,
            "accebtable" =>( $accebtable / $thesisCount) * 100,
            "rejected" =>( $rejected / $thesisCount) * 100,
        ];
    }

    public static function thesisStatisticsForUser($id){
        $thesisCount = UserBook::join('thesis', 'user_book.id', '=', 'thesis.user_book_id')->where('user_id',$id)->count();
        $very_excellent =  UserBook::join('thesis', 'user_book.id', '=', 'thesis.user_book_id')->where('user_id',$id)->where('degree','<=',100)->count();
        $excellent =UserBook::join('thesis', 'user_book.id', '=', 'thesis.user_book_id')->where('user_id',$id)->where('degree','<',95)->count();
        $veryGood =  UserBook::join('thesis', 'user_book.id', '=', 'thesis.user_book_id')->where('user_id',$id)->where('degree','<',85)->count();
        $good = UserBook::join('thesis', 'user_book.id', '=', 'thesis.user_book_id')->where('user_id',$id)->where('degree','<',80)->count();
        $accebtable = UserBook::join('thesis', 'user_book.id', '=', 'thesis.user_book_id')->where('user_id',$id)->where('degree','<',70)->count();
        return [
            "total" => $thesisCount,
            "very_excellent" =>( $very_excellent / $thesisCount) * 100,
            "excellent" =>( $excellent / $thesisCount) * 100,
            "very_good" =>( $veryGood / $thesisCount) * 100,
            "good" =>( $good / $thesisCount) * 100,
            "accebtable" =>( $accebtable / $thesisCount) * 100
        ];
    }



    public function updatePicture(Request $request){
        $validator = Validator::make($request->all(), [
            'path' => 'required',
            'image' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $input = $request->all();
        $photo = Photos::where('path',$input['path'])->first();
        $newPath = $this->updateThesisMedia($input['image'], $photo->path);
        $photo->path = $newPath;
        $photo->save();
        return $this->jsonResponseWithoutMessage('Photo updated',$photo, 200);

        
    }


    public function deletePhoto($id){
        $photo = Photos::find($id);
        $this->deleteThesisMedia($photo->path);
        $photo->delete();
    }


    public function getThesisPhotosCount($user_book_id){
        $thesis = EligibleGeneralThesis::where("user_book_id",$user_book_id)->has('photos')->count();
        return $this->jsonResponseWithoutMessage('photos count',$thesis, 200);

    }


}
