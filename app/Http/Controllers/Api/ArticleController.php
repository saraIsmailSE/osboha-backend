<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    use ResponseJson;
    /**
     * Return all stored articles in the system.
     *
     * @return jsonResponseWithoutMessage
     */
    public function index()
    {
        #######ASMAA#######

        $articles = Article::with('post')->with('user')->get();

        if($articles->isNotEmpty()){
            //found articles response
            return $this->jsonResponseWithoutMessage(ArticleResource::collection($articles), 'data', 200);
        }else{
            //not found articles response
            throw new NotFound;

            //return $this->jsonResponseWithoutMessage('No Records', 'data', 204);
        }
    }

     /**
     * Add a new article to the system (“create article” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {
        #######ASMAA#######

        //validate requested data
        $validator = Validator::make($request->all(), [
            'title' => 'required', 
            'post_id' => 'required',
           // 'user_id' => 'required',
            'section' => 'required',            
        ]);

        if($validator->fails()){
            //return validator errors
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if(Auth::user()->can('create article')){      
            //create new article
            $article = Article::create([
                'title' => $request->title, 
                'post_id' => $request->post_id,
                'user_id' => Auth::id(),
                'section' => $request->section,
            ]); 
            //success response after creating the article
            return $this->jsonResponse(new ArticleResource($article), 'data', 200,'Article Created Successfully');
        }else{
            //unauthorized user response
            throw new NotAuthorized;

            //return $this->jsonResponseWithoutMessage('Unauthorized', 'data', 401);
        }
    }
    
     /**
     * Find and show an existing article in the system by its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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
            return $this->jsonResponseWithoutMessage(new ArticleResource($article), 'data', 200);
        }else{
            //article not found response
            throw new NotFound;

            //return $this->jsonResponseWithoutMessage('Article not found', 'data', 204);
        }
    }

     /**
     * Update an existing article’s details ( “edit article” permission is required).
     * In order to update the article, the logged in user_id has to match the user_id in the request.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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
        if(Auth::user()->can('edit article') && (Auth::id() == $request->user_id)){
            //find needed article
            $article = Article::find($request->article_id);

            //update found article
           if ($article) {
               $article->update($request->all());

               //success response after update
               return $this->jsonResponse(new ArticleResource($article), 'data', 200, 'Article Updated Successfully');
           }else{
               throw new NotFound();
           }

        }else{
            //unauthorized user response
            throw new NotAuthorized;

            //return $this->jsonResponseWithoutMessage('Unauthorized', 'data', 401);
        }
    }
    
    /**
     * Delete an existing article in the system using its id (“delete article” permission is required). 
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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

            if($article) {
                //delete found article
                $article->delete();

                //success response after delete
                return $this->jsonResponse(new ArticleResource($article), 'data', 200, 'Article Deleted Successfully');
            }else{
                throw new NotFound();
            }
        }else{
            //unauthorized user response
            throw new NotAuthorized;

            //return $this->jsonResponseWithoutMessage('Unauthorized', 'data', 401);
        }
    }
    
    /**
     * Find and return all articles related to a user using user_id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */    
    public function listAllArticlesByUser(Request $request)
    {
        #######ASMAA#######

        //validate article id
        $validator = Validator::make($request->all(), [
            'user_id' => 'required'
        ]);

        //validator errors response
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        //find articles belong to user
        $articles = Article::where('user_id', $request->user_id)->get();

        if($articles->isNotEmpty()){
            //found articles response (display data)
            return $this->jsonResponseWithoutMessage(ArticleResource::collection($articles), 'data', 200);
        }else{
            //not found articles exception
            throw new NotFound;
        }
        //ArticleResource::collection(Article::with())
    }
}
