<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BookController extends Controller
{
    use ResponseJson;

    public function index()
    {
        $books = Book::all();
        if($books){
            return $this->jsonResponseWithoutMessage($books, 'data',200);
        }
        else{
           // throw new NotFound;
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
            'picture' => 'required',
            'level' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('create book')){
            Book::create($request->all());
            return $this->jsonResponseWithoutMessage("Book Craeted Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
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
            return $this->jsonResponseWithoutMessage($book, 'data',200);
        }
        else{
           // throw new NotFound;
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
            'picture' => 'required',
            'level' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit book')){
            $book = Book::find($request->section_id);
            $book->update($request->all());
            return $this->jsonResponseWithoutMessage("Book Updated Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
        }
        
    }

    public function destroy(Request $request)
    {

        if(Auth::user()->can('delete book')){
            $section = Book::find($request->section_id);
            $section->delete();
            return $this->jsonResponseWithoutMessage("Book Deleted Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;
    
        }
    }
}
