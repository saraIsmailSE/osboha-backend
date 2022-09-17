<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookType;
use App\Models\Book;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\BookTypeResource;

class BookTypeController extends Controller
{
    use ResponseJson;
    /**
     * Read all book types in the system.
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        $bookTypes = BookType::all();
        if($bookTypes->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(BookTypeResource::collection($bookTypes), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

    /**
     *Add a new book type to the system (“create type” permission is required)
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('create type')){
            BookType::create($request->all());
            return $this->jsonResponseWithoutMessage("Book-Type Created Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;   
        }
    }

    /**
     * Find and show an existing book type in the system by its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $bookType = BookType::find($request->id);
        if($bookType){
            return $this->jsonResponseWithoutMessage(new BookTypeResource($bookType), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }
    /**
     * Update an existing book type’s using its id( “edit type” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'type' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit type')){
            $bookType = BookType::find($request->id);
            if($bookType){
                $bookType->update($request->all());
                return $this->jsonResponseWithoutMessage("Book-Type Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;   
            }
        }
        else{
            throw new NotAuthorized;   
        }
        
    }
    /**
     * Delete an existing book type’s in the system using its id (“delete type” permission is required). 
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete type')){
            $bookType = BookType::find($request->id);
            
            if($bookType){

                Book::where('type_id',$request->id)
                    ->update(['type_id'=> 0]);
                    $bookType->delete();
                
                return $this->jsonResponseWithoutMessage("Book-Type Deleted Successfully", 'data', 200);
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
