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

/**
 * Description: Controller for Osboha group with operations.
 *
 * Methods:
 *  - CRUD.
 *  - rules() for validation.
 *  - upload() for upload and file validation.
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

        return $this->jsonResponseWithoutMessage($group,'data', 200);
    }

    /**
     * Create new group
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $rules=$this->rules();
        $input=$request->all();
        $validator=Validator::make($input,$rules);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
          }

        if($request->hasFile('cover_picture'))
        {
            $file=$request->file('cover_picture');
            $input['cover_picture']=$this->upload($file);
        }

        $group=Group::create($input);
        
        return $this->jsonResponse($group,'data', 200, 'Group Created');

    }

    /**
     * Display the specified group.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {

        $rules=$this->rules();
        $input=$request->all();
        $validator=Validator::make($input,$rules);

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

        $group=Group::findOrFail($request->id);
        $group->update($input);

        if($oldImage && $oldImage != $group->cover_picture){
            Storage::disk('public')->delete($oldImage);
        }
        
        return $this->jsonResponse($group,'data', 200, 'Group Updated');
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

        $group=Group::findOrFail($request->group_id);
        $group->delete();

        if($group->cover_picture){
            Storage::disk('public')->delete($group->cover_picture);
        }

        return $this->jsonResponseWithoutMessage('Group Deleted', 'data', 200);

    }

    /**
     *
     * Method for validation rules
     * used by create() and update()
     * @return array
     */
    public function rules(){
        return [
         'name' => 'required',
         'description' => 'nullable|string',
         'type' => 'required|string',
         'cover_picture' => 'nullable|image|mimes:jpg,jpeg,png',
         'creator_id' => 'required|int'
        ];
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

            return $file->storeAs('group',$fileName,[
                'disk' => 'public'
            ]);
        } 
        else{
            return $this->jsonResponseWithoutMessage('File Not Valid', 'data', 500);
        }
        
    }

}
