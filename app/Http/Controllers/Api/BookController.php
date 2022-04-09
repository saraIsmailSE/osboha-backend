<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Traits\ResponseJson;
use App\Models\Media;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\BookResource;

class BookController extends Controller
{
    use ResponseJson, MediaTraits;

    public function index()
    {
        $books = Book::all();
        if($books->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(BookResource::collection($books), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'writer' => 'required',
            'publisher' => 'required',
            'brief' => 'required',
            'start_page' => 'required',
            'end_page' => 'required',
            'link' => 'required',
            'section' => 'required',
            'type' => 'required',
            'image' => 'required',
            'level' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('create book')){
            $book=Book::create($request->all());
            $this->createMedia($request->file('image'), $book->id, 'book');
            return $this->jsonResponseWithoutMessage("Book Craeted Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;   
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $book = Book::find($request->book_id);
        if($book){
            return $this->jsonResponseWithoutMessage(new BookResource($book), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'writer' => 'required',
            'publisher' => 'required',
            'brief' => 'required',
            'start_page' => 'required',
            'end_page' => 'required',
            'link' => 'required',
            'section' => 'required',
            'type' => 'required',
            'image' => 'required',
            'level' => 'required',
            'book_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit book')){
            $book = Book::find($request->book_id);
            if($book){
                $currentMedia= Media::where('comment_id', $book->id)->first();
                // if exists, update
                if($currentMedia){
                    $this->updateMedia($request->file('image'), $currentMedia->id);
                }
                //else create new one
                else {
                    // upload media
                    $this->createMedia($request->file('image'), $book->id, 'book');
                }
                $book->update($request->all());
                return $this->jsonResponseWithoutMessage("Book Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;   
            }
        }
        else{
            throw new NotAuthorized;   
        }
        
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete book')){
            $book = Book::find($request->book_id);
            if($book){
                //check Media
                $currentMedia = Media::where('comment_id', $book->id)->first();
                // if exist, delete
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }
                $book->delete();
                return $this->jsonResponseWithoutMessage("Book Deleted Successfully", 'data', 200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }
    }
}
