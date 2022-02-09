<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    use ResponseJson;
    
    public function index()
    {
        #######ASMAA#######

        $articles = Article::all();

        if($articles){
            //found articles response
            return $this->jsonResponseWithoutMessage($articles, 'data', 200);
        }else{
            //not found articles response
            return $this->jsonResponseWithoutMessage('No Records', 'data', 204);
        }
    }

    public function create(Request $request)
    {
        #######ASMAA#######

        //validate requested data
        $validator = Validator::make($request->all(), [
            'title' => 'required', 
            'post_id' => 'required',
            'user_id' => 'required',
            'section' => 'required',            
        ]);

        if($validator->fails()){
            //return validator errors
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if(Auth::user()->can('create article')){      
            //create new article      
            Article::create($request->all()); 

            //success response after creating the article
            return $this->jsonResponseWithoutMessage('Article created successfully', 'data', 200);
        }else{
            //unauthorized user response
            return $this->jsonResponseWithoutMessage('Unauthorized', 'data', 401);
        }
    }

    public function show(Request $request)
    {
        #######ASMAA#######

        //validate article id 
        $validator = Validator::make($request->all(), [
            'article_id' => 'required'
        ]);

        //validator errors response
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //find needed article
        $article = Article::find($request->article_id);

        if($article){
            //return found article
            return $this->jsonResponseWithoutMessage($article, 'data', 200);
        }else{
            //article not found response
            return $this->jsonResponseWithoutMessage('Article not found', 'data', 204);
        }
    }

    public function update(Request $request)
    {
        #######ASMAA#######

         //validate requested data
         $validator = Validator::make($request->all(), [
            'title'      => 'required', 
            'post_id'    => 'required',
            'user_id'    => 'required',
            'section'    => 'required',
            'article_id' => 'required',            
        ]);

        if($validator->fails()){
            //return validator errors
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if(Auth::user()->can('update article')){
            //find needed article
            $article = Article::find($request->article_id);

            //update found article
            $article->update($request->all()); 

            //success response after update
            return $this->jsonResponseWithoutMessage('Article updated successfully', 'data', 200);
        }else{
            //unauthorized user response
            return $this->jsonResponseWithoutMessage('Unauthorized', 'data', 401);
        }
    }

    public function delete(Request $request)
    {
        #######ASMAA#######

        //validate article id 
        $validator = Validator::make($request->all(), [
            'article_id' => 'required'
        ]);

        //validator errors response
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if(Auth::user()->can('delete article')){

            //find needed article 
            $article = Article::find($request->article_id);

            //delete found article
            $article->delete();

            //success response after delete
            return $this->jsonResponseWithoutMessage('Article deleted successfully', 'data', 200);
        }else{
            //unauthorized user response
            return $this->jsonResponseWithoutMessage('Unauthorized', 'data', 401);
        }
    }
}
