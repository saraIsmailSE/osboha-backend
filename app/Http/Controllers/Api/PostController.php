<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class PostController extends Controller
{
    use ResponseJson;
   
    public function index()
    {
        $posts = Post::all();

        if($posts){
            return $this->jsonResponseWithoutMessage($posts, 'data',200);
        }
        else{
           // throw new NotFound;
        }
    }

    public function create(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'body' => 'required',
            'user_id' => 'required',
            'type' => 'required',
            'allow_comments' => 'required',
            'tag' => 'required',
            'vote' => 'required',
            'is_approved' => 'required',
            'is_pinned' => 'required',
            'timeline_id' => 'required',
        ]);
     
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('create post')){
            Post::create($request->all());
            return $this->jsonResponseWithoutMessage("Post Craeted Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $post = Post::find($request->post_id);
        if($post){
            return $this->jsonResponseWithoutMessage($post, 'data',200);
        }
        else{
           // throw new NotFound;
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required',
            'user_id' => 'required',
            'type' => 'required',
            'allow_comments' => 'required',
            'tag' => 'required',
            'vote' => 'required',
            'is_approved' => 'required',
            'is_pinned' => 'required',
            'timeline_id' => 'required',
            'post_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit post')){
            $post = Post::find($request->book_id);
            $post->update($request->all());
            return $this->jsonResponseWithoutMessage("Post Updated Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete post')){
            $post = Post::find($request->post_id);
            $post->delete();
            return $this->jsonResponseWithoutMessage("Post Deleted Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;
        }
    }
}
