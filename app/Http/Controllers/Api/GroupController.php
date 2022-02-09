<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Traits\ResponseJson;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Description: GroupController for Osboha group .
 *
 * Methods:
 *  - CRUD.
 *  - upload() for upload file and validate it.
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
          return $this->jsonResponseWithoutMessage("No Result found", 'data', 200);
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
            $input['cover_picture']=$this->upload($file);
        }

        if(Auth::user()->can('create group')){
         $group=Group::create($input);
         return $this->jsonResponse($group,'data', 200, 'Group Created');
        }
        else{
         return $this->jsonResponseWithoutMessage("You Are Not Authorized", 'data', 403);
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

        $group=Group::findOrFail($request->group_id);

        return $this->jsonResponseWithoutMessage($group, 'data', 200);

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

        //get old image(before update) to delete it after update 
        $oldImage=$request->cover_picture;

        if($request->hasFile('cover_picture'))
        {
            $file=$request->file('cover_picture');
            $input['cover_picture']=$this->upload($file);
        }

        if(Auth::user()->can('edit group')){
          $group=Group::findOrFail($request->group_id);
          $group->update($input);

          if($oldImage && $oldImage != $group->cover_picture){
            Storage::disk('public')->delete($oldImage);
            }

           return $this->jsonResponse($group,'data', 200, 'Group Updated');
        }//endif Auth

        else{
         return $this->jsonResponseWithoutMessage("You Are Not Authorized", 'data', 403);
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
         $group=Group::findOrFail($request->group_id);
         $group->delete();

        if($group->cover_picture){
            Storage::disk('public')->delete($group->cover_picture);
        }

        return $this->jsonResponseWithoutMessage('Group Deleted', 'data', 200);
        }//endif Auth

        else {
         return $this->jsonResponseWithoutMessage("You Are Not Authorized", 'data', 403);
        }

    }

    /**
     *
     * Method for upload and validate image file
     * used by create() and update()
     * @return object
     */
    public function upload(UploadedFile $file)
    {
        if($file->isValid()){   
            $fileName=uniqid().'_'.$file->getClientOriginalName();

            return $file->storeAs('images',$fileName,[
                'disk' => 'public'
            ]);
        } 
        else{
            return $this->jsonResponseWithoutMessage('File Not Valid', 'data', 500);
        }
        
    }

}
