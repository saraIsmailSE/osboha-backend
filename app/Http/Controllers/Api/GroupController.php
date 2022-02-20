<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Traits\ResponseJson;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Description: GroupController for Osboha group.
 *
 * Methods: CRUD.
 *  
 */

class GroupController extends Controller
{

    use ResponseJson;

    /**
     * Display groups list
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $group= Group::all();

        if($group){
          return $this->jsonResponseWithoutMessage($group,'data', 200);
        }
        else{
          // throw new NotFound;
        }
    }

    /**
     * Create new group
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

        $input=$request->all();

        $validator=Validator::make($input,[
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'cover_picture' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'creator_id' => 'required|int'
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
          }

        if($request->hasFile('cover_picture'))
        {
            $file=$request->file('cover_picture');
            $fileName=time().'.'.$file->extension();
            $file->move(public_path('assets/images'),$fileName);
            $input['cover_picture']=$fileName;
        }

     if(Auth::user()->can('create group')){
         $group=Group::create($input);
         return $this->jsonResponse($group,'data', 200, 'Group Created');
      }
        else{
            //throw new NotAuthorized;   
        }
    }

    /**
     * Display the specified group.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        $group=Group::find($request->group_id);

        if($group){
         return $this->jsonResponseWithoutMessage($group, 'data', 200);
        }

        else{
            // throw new NotFound;
        }

    }

    /**
     * Update the specified group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $input=$request->all();
        $validator=Validator::make($input,[
            'group_id' => 'required',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'cover_picture' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'creator_id' => 'required|int'
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
          }

        $group=Group::find($request->group_id);  

        //get old image to delete it after update 
        $oldImage=$group->cover_picture;

        if($request->hasFile('cover_picture'))
        {
            $file=$request->file('cover_picture');
            $fileName=time().'.'.$file->extension();
            $file->move(public_path('assets/images'),$fileName);
            $input['cover_picture']=$fileName;
        
        }

      if(Auth::user()->can('edit group')){
          $group->update($input);
          
          //delete old image
          File::delete(public_path('assets/images/'.$oldImage));

          return $this->jsonResponse($group,'data', 200, 'Group Updated');
        }//endif Auth

        else{
            //throw new NotAuthorized;   
        }
    }

    /**
     * Delete the created group
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete group')){
         $group=Group::find($request->group_id);
         $group->delete();

        if($group->cover_picture){
           File::delete(public_path('assets/images/'.$group->cover_picture));
        }

        return $this->jsonResponseWithoutMessage('Group Deleted', 'data', 200);
        }//endif Auth

        else {
            //throw new NotAuthorized;   
        }

    }

}
