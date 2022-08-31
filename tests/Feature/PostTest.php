<?php

namespace Tests\Feature;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;







class PostTest extends TestCase {


/*
    //user_id=6/ambasador  user_id=1/admin user_id=4/leader
    public function test_user_can_create_post_in_main_withoutPermison()
     {
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);


        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('post/create', [
                'body'=>' The more you read, the more your vocabulary grows, along with your ability to effectively communicate',
                'type_id' =>'1',//normal
                'timeline_id'=>"1"//main
        ]);
        $response->assertStatus(403);

    }

    

    public function test_user_can_create_post_in_profile_with_body()
     {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('post/create', [
                'body'=>'By reading books about protagonists who have overcome challenges',
                'type_id' =>'1',//normal
                'timeline_id'=>"2"//profile
        ]);
        $response->assertok();

    }

    public function test_user_can_create_post_in_profile_With_image()
     {
        $this->withoutExceptionHandling();
         Storage::fake('imageCreate');
        $file = UploadedFile::fake()->image('imageCreate.jpn');


        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('post/create', [
                'image'=>$file,
                'type_id' =>'1',//normal
                'timeline_id'=>"2"//profile
        ]);
        $response->assertok();

    }

    public function test_user_can_create_post_in_profile_with_Body_and_image()
     {
        $this->withoutExceptionHandling();
        Storage::fake('withBoth');
        $file = UploadedFile::fake()->image('withBoth.jpn');

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('post/create', [
                'body'=>'By reading books about protagonists who have overcome challenges',   
                'image'=>$file,
                'type_id' =>'1',//normal
                'timeline_id'=>"2"//profile
        ]);
        $response->assertok();

    }

    public function test_user_can_create_post_in_Group()
     {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('post/create', [
                'body'=>'By reading books about protagonists who have overcome challenges',
                'type_id' =>'1',//normal
                'timeline_id'=>"4"//group
        ]);
        $response->assertok();

    }
    //user_id=4 leader
    public function test_leader_can_create_post_in_Group()
     {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 7|zU0Mh2eyY8kXIN5pSVPZhE6vLX4NFN6QWndzyEJF',
        ])->post('post/create', [
                'body'=>'By reading books about protagonists who have overcome challenges',
                'type_id' =>'1',//normal
                'timeline_id'=>"2",//profile
                'tag' => ['Ron Hansen', 'Blake Gaylord'],
                'vote' => ['yes','No'],
        ]);
        $response->assertok();

    }

    public function test_user_can_not_create_post_missed_fieldes()
     {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('post/create', [
                'body'=>'By reading books about protagonists who have overcome challenges',
                'tag' => ['Ron Hansen', 'Blake Gaylord'],
                'vote' => ['yes'],
        ]);
        $response->assertJsonMissingValidationErrors(['timeline_id','type_id']);

    }


/*
    //--------------------------------End Create-------------------------------------
    

    // Test for Show Post function  

    public function test_ShOwPost(){

        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/show', [
                'post_id'=>'1'
        ]);
        $response->assertOk();


    }

    public function test_ShowPostValidateFails(){

        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/show');
        $response->assertJsonMissingValidationErrors(['post_id']);

    }

    public function test_ShowPostNotFound(){

        $this->withoutExceptionHandling();
        $this->expectException(NotFound::class);


        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/show', [
                'post_id'=>'100'
        ]);
         return$response->assertStatus(404);

         
    }

    //--------------------------End Show-------------------------------------------

    
    // Test for Update Post function 

    public function test_user_update_him_post() {

        
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/update', [
            'body' => 'Reading is an essential part of literacy, yet from a historical perspective literacy is about having the ability to both read and write',
            'type_id' => '0',
            'timeline_id' => '1',
            'post_id' => '2',
            'tag' => ['user_id'],
        ]);
        $response->assertOk();
    }

    public function test_user_update_him_post_with_image_does_not_exist() {

        
        $this->withoutExceptionHandling();
        Storage::fake('hellos');
        $file = UploadedFile::fake()->image('hello.pnj');
       
        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/update', [
            'body' => 'Reading is an essential part of literacy',
            'type_id' => '1',
            'timeline_id' => '1',
            'image'=>$file, 
            'post_id' => '3',
            'tag' => ['user_id'],
            
        ]);
        $response->assertOk();
    }
    
    public function test_admin_can_not_update_post() {
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);


        $response = $this->withHeaders([
        'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
        ])->post('/post/update', [
            'body' => 'Reading is an essential part of literacy, yet from a historical perspective literacy is about having the ability to both read and write',
            'type_id' => '1',
            'timeline_id' => '1',
            'post_id' => '9',
            'tag' => ['user_id'],
        ]);
        $response->assertStatus(403);
        
    }

    public function test_any_user_can_not_update_post() {
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);


        $response = $this->withHeaders([
        'Authorization' => 'Bearer 6|s7gMqTULqY9nSNQw7EdjzJ5vthtKalTORjoteqsi',
        ])->post('/post/update', [
            'body' => 'Reading is an essential part of literacy, yet from a historical perspective literacy is about having the ability to both read and write',
            'type_id' => '1',
            'timeline_id' => '1',
            'post_id' => '2',
            'tag' => ['user_id'],
        ]);
        $response->assertStatus(403);
    }

    public function test_update_post_missed_fieldes(){

        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/update', [
            'body' => 'Reading is an essential part of literacy, yet from a historical perspective literacy is about having the ability to both read and write',
            'type_id' => '1',
            
        ]);
        $response->assertJsonMissingValidationErrors(['timeline_id']);

    }

    //----------------------------EndUpdate-----------------------------------------
    

    // Test for delet Post function 
    
    public function test_user_delete_him_post() {

        
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/delete', [
            'post_id' => '26'
        ]);
        $response->assertOk();
    }

    public function test_admin_can_delete_post() {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
        ])->post('/post/delete', [
            'post_id' => '25'
        ]);
        $response->assertOk();
        
    }

    public function test_any_user_canNot_delete_post() {
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);


        $response = $this->withHeaders([
        'Authorization' => 'Bearer 6|s7gMqTULqY9nSNQw7EdjzJ5vthtKalTORjoteqsi',
        ])->post('/post/delete', [
            'post_id' => '24'
        ]);
        $response->assertStatus(403);
    }

    public function test_delete_post_missed_fieldes(){

        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/delete');
        $response->assertJsonMissingValidationErrors(['post_id']);

    }

    public function test_delet_Post_Not_Found(){

        $this->withoutExceptionHandling();
        $this->expectException(NotFound::class);


        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/post/show', [
                'post_id'=>'100'
        ]);
        $response->assertStatus(404);

         
    }

    //-------------------------End Delete--------------------------------------------

/*
    //test postByTimelineId function
    
    public function test_postByTimeLineId_missed_fieldes(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/postByTimelineId');
        
        $response->assertJsonMissingValidationErrors(['timeline_id']);
    

    }

    public function test_postByTimeLineId_post_not_empty(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/postByTimelineId', [
                'timeline_id'=>'2'
            ]);
        
        $response->assertok();
    

    }

    public function test_postByTimeLineId_Not_Found(){
        $this->withoutExceptionHandling();
        $this->expectException(NotFound::class);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/postByTimelineId', [
                'timeline_id'=>'4'
            ]);
        
        $response->assertStatus(404);
    

    }

    //----------------End PostByTimeLineId---------

    //test postByUserId function

    public function test_ppostByUserId_missed_fieldes(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/postByUserId');
        
        $response->assertJsonMissingValidationErrors(['user_id']);
    

    }

    public function test_postByUserId_post_not_empty(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/postByUserId', [
                'user_id'=>'6'
            ]);
        
        $response->assertok();
    

    }

    public function test_postByUserId_Not_Found(){
        $this->withoutExceptionHandling();
        $this->expectException(NotFound::class);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/postByUserId', [
                'user_id'=>'3000'
            ]);
        
        $response->assertStatus(404);
    

    }

    //-----------------End PostByUserId--------------------

    //test listPostsToAccept

    public function test_listPostsToAccept__missed_fieldes(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/PostsToAccept');
        
        $response->assertJsonMissingValidationErrors(['timeline_id']);
    

    }

    public function test_listPostsToAccept_in_group_user_non_display(){
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/PostsToAccept', [
                'timeline_id'=>'4'
            ]);
        $response->assertStatus(403);
    }

    public function test_listPostsToAccept_in_group_admin(){
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/PostsToAccept', [
                'timeline_id'=>'4'
            ]);
        $response->assertok();
    }

    public function test_listPostsToAccept_in_group_Leader(){
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 7|zU0Mh2eyY8kXIN5pSVPZhE6vLX4NFN6QWndzyEJF',
            ])->post('/post/PostsToAccept', [
                'timeline_id'=>'4'
            ]);
        $response->assertok();
    }
    

    public function test_listPostsToAccept_not_found_in_groupe(){
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/PostsToAccept', [
                'timeline_id'=>'4'
            ]);
        $response->assertStatus(404);
    }
   
    //------------------ end test listPostToAccept--------

/*
    // test AcceptPost function
    public function test_AcceptPost_missed_fieldes(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/acceptPost');
        
        $response->assertJsonMissingValidationErrors(['post_id']);
    

    }

    public function test_AcceptPost_is_approved_not_Null(){ 
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/acceptPost', [
                'post_id'=>'4'
            ]);
        $response->assertok();
    }

    public function test_AcceptPost_is_approved_Null(){
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/acceptPost', [
                'post_id'=>'3'
            ]);
        $response->assertok();
    }

    public function test_AcceptPost_not_found(){
        $this->withoutExceptionHandling();
        $this->expectException(NotFound::class);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/acceptPost', [
                'post_id'=>'1478'
            ]);
        $response->assertStatus(404);
    }
    public function test_AcceptPost_without_permission(){
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/acceptPost' , [
                'post_id'=>'1'
            ]);
        $response->assertStatus(403);
    }

    //----------------------End accpetPost----------

    //test declinePost function

    public function test_declinePost_missed_fieldes(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/declinePost');
        
        $response->assertJsonMissingValidationErrors(['post_id']);
    

    }

    public function test_declinePost_is_approved_not_Null(){ 
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/declinePost', [
                'post_id'=>'5'
            ]);
        $response->assertok();
    }

    public function test_declinePost_is_approved_Null(){
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/declinePost', [
                'post_id'=>'6'
            ]);
        $response->assertok();
    }

    public function test_declinePost_not_found(){
        $this->withoutExceptionHandling();
        $this->expectException(NotFound::class);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/declinePost', [
                'post_id'=>'2078'
            ]);
        $response->assertStatus(404);
    }
    
    public function test_declinePost_without_permission(){
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/declinePost' , [
                'post_id'=>'1'
            ]);
        $response->assertStatus(403);
    }
 //-----------End Test declinePost-------
 
   //controllComments function
   public function test_controllComments_missed_fieldes(){
         $this->withoutExceptionHandling();
         $response = $this->withHeaders([
                'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
            ])->post('/post/controllComments',[
                 'allow_comments' => '1',
            ]);
    
        $response->assertJsonMissingValidationErrors(['post_id']);


     }

     public function test_controllComments_closed_by_auth(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
           ])->post('/post/controllComments',[
                'allow_comments' => '0',
                'post_id'=>'8'
           ]);
   
       $response->assertok();


    }

    public function test_controllComments_opened_by_auth(){ //alleardy close
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
           ])->post('/post/controllComments',[
                'allow_comments' => '1',
                'post_id'=>'7'
           ]);
   
       $response->assertok();


    }

    public function test_controllComments_closed_by_admin(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
           ])->post('/post/controllComments',[
                'allow_comments' => '0',
                'post_id'=>'10'
           ]);
   
       $response->assertok();


    }

    public function test_controllComments_opened_by_admin(){ //alleardy close
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
           ])->post('/post/controllComments',[
                'allow_comments' => '1',
                'post_id'=>'9'
           ]);
   
       $response->assertok();
    }

    public function test_controllComments_closed_by_any_user(){
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 6|s7gMqTULqY9nSNQw7EdjzJ5vthtKalTORjoteqsi',
           ])->post('/post/controllComments',[
                'allow_comments' => '0',
                'post_id'=>'8'
           ]);
   
       $response->assertStatus(403);


    }

    public function test_controllComments_opened_by_any_user(){ //alleardy close
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 6|s7gMqTULqY9nSNQw7EdjzJ5vthtKalTORjoteqsi',
           ])->post('/post/controllComments',[
                'allow_comments' => '1',
                'post_id'=>'7'
           ]);
   
       $response->assertStatus(403);
    }   

    public function test_controllComments_not_found(){
        $this->withoutExceptionHandling();
        $this->expectException(NotFound::class);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/controllComments', [
                'allow_comments' => '1',
                'post_id'=>'2078'
            ]);
        $response->assertStatus(404);
    }

    
    //------------End controllComment-----

    //test pinnPostFunction

    public function test_pinnPost_missed_fieldes(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
           ])->post('/post/pinnPost',[
            'is_pinned' => '1',
           ]);
   
       $response->assertJsonMissingValidationErrors(['post_id']);


    }

    public function test_pinnPost_pinned_by_auth(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
           ])->post('/post/pinnPost',[
                'is_pinned' => '0',
                'post_id'=>'11
                '
           ]);
   
       $response->assertok();


    }

    public function test_pinnPost_Unpinned_by_auth(){ //alleardy pinned
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
           ])->post('/post/pinnPost',[
                'is_pinned' => '1',
                'post_id'=>'12'
           ]);
   
       $response->assertok();


    }

    public function test_pinnPost_pinned_by_admin(){
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
           ])->post('/post/pinnPost',[
               'is_pinned' => '0',
               'post_id'=>'13'
           ]);
   
       $response->assertok();


    }

    public function test_pinnPost_Unpinned__by_admin(){ 
        $this->withoutExceptionHandling();
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
           ])->post('/post/pinnPost',[
                'is_pinned' => '1',
                'post_id'=>'14'
           ]);
   
       $response->assertok();
    }

    public function test_pinnPost_pinned_by_any_user(){
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 6|s7gMqTULqY9nSNQw7EdjzJ5vthtKalTORjoteqsi',
           ])->post('/post/pinnPost',[
                'is_pinned' => '0',
                'post_id'=>'15'
           ]);
   
       $response->assertStatus(403);


    }

    public function test_pinnPost_Unpinned_by_any_user(){ 
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);
        $response = $this->withHeaders([
               'Authorization' => 'Bearer 6|s7gMqTULqY9nSNQw7EdjzJ5vthtKalTORjoteqsi',
           ])->post('/post/pinnPost',[
                'is_pinned' => '1',
                'post_id'=>'16'
           ]);
   
       $response->assertStatus(403);
    }   

    public function test_pinnPost_not_found(){
        $this->withoutExceptionHandling();
        $this->expectException(NotFound::class);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
            ])->post('/post/pinnPost', [
                'is_pinned' => '1',
                'post_id'=>'3078'
            ]);
        $response->assertStatus(404);
    }
*/
}