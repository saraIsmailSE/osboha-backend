<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Group as GroupResource;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Media;
use App\Models\Timeline;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Description: GroupController for Osboha group.
 *
 * Methods: 
 * - CRUD
 * - group posts list
 */

class GroupController extends Controller
{

    use ResponseJson, MediaTraits;

    /**
     * Display groups list
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $group= Group::all();
        if(Auth::user()->can('list groups')){
          return $this->jsonResponseWithoutMessage(GroupResource::collection($group),'data', 200);
        }

        else{
          throw new NotAuthorized;
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'creator_id' => 'required|int'
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
          }

        $group=Group::create($input);
         
        if(Auth::user()->can('create group')){
            if($request->hasFile('cover_picture'))
            {  
                $file=$request->file('cover_picture');
                $this->createMedia($file,$group->id,'group');
            }
            return $this->jsonResponseWithoutMessage('Group Craeted', 'data', 200);  
        }
            else{
                throw new NotAuthorized;   
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
         $users=$group->user;
         
         foreach($users as $user){
           if(Auth::id()==$user->id){
            return $this->jsonResponseWithoutMessage(new GroupResource($group), 'data', 200);
           }

           else{
               throw new NotFound;
           }
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'creator_id' => 'required|int'
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
          }

        $group=Group::find($request->group_id);  

      if(Auth::user()->can('edit group'))
      {
        if($request->hasFile('image'))
        {
            $file=$request->file('image');
            $currentMedia=Media::where('group_id',$group->id);

            if($currentMedia){
                $this->updateMedia($file, $currentMedia->id);
            }
            
            else{
                $this->createMedia($file,$group->id,'group');
            }
            
        }
          $group->update($input);
          return $this->jsonResponseWithoutMessage("Group Updated", 'data', 200);
    
       }//endif Auth

        else{
            throw new NotAuthorized;   
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

        if(Auth::user()->can('delete group')) {
         $group=Group::find($request->group_id);
         $currentMedia=Media::where('group_id',$group->id);
         
         //if exist delete image
         if($currentMedia) {
          $this->deleteMedia($currentMedia->id);
         }

         $group->delete();

        return $this->jsonResponseWithoutMessage('Group Deleted', 'data', 200);
        }//endif Auth

        else {
            throw new NotAuthorized;   
        }

    }

    public function list_group_posts($group_id)
    {

        $group=Group::find($group_id);
        $timeLine=Timeline::find($group->timeline_id)->posts;

        if($timeLine){
         return $timeLine;
        }
        
        else{
          throw new NotFound;
        }
    }

}