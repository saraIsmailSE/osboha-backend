<?php

namespace Tests\Feature;
use App\Exceptions\NotFound;
use App\Models\Book;
use App\Exceptions\NotAuthorized;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase; 


class BookTest extends TestCase
{
    /**
     * Add book.
     *
     * @return void
     */
    //in this test casse using two token one for (admin) user_id=1  one for  (ambasador) user_id=6
    public function test_user_with_permession_can_add_new_book_without_missed_feildes()
    {

        $this->withoutExceptionHandling();
        $file = UploadedFile::fake()->image('avatar.jpeg');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
        ])
            ->post(
                '/book/create',
                [
                    'name'       => 'Book 2',
                    'writer'     => 'Writer 2',
                    'publisher'  => 'Publisher 1',
                    'brief'      => 'required',
                    'start_page' => 1,
                    'end_page'   => 200,
                    'link'       => 'https://www.google.com/',
                    'section_id' => 1,
                    'type_id'    => '6',
                    'image'      =>$file,
                    'level'      =>'level'
                ]
            );
          
        $response->assertstatus(200);
}

        public function test_user_with_permession_cannot_add_new_book_with_missed_fieldes()
        {
            
            /**
            * @expectedException NotAuthorized
            */

            $this->withoutExceptionHandling();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
            ])
                ->post(
                    '/book/create',
                    [
                        'name' => 'Book 1',
                        'writer' => 'Writer 1',
                        'publisher' => 'Publisher 1',
                        'brief' => 'required',
                        'start_page' => 1,
                        'end_page' => 200,
                        'link' => 'https://www.google.com/',
                        'section_id' => 2,
                        'type_id' => '6',
                    ]
                );

            $response->assertJsonMissingValidationErrors(['level', 'image']);
        }

        public function test_user_with_no_permission_cannot_add_new_book()
        {
            /**
            * @expectedException NotAuthorized
            */
            $this->withoutExceptionHandling();
            $this->expectException(NotAuthorized::class);
            Storage::fake('avatars');
            $file = UploadedFile::fake()->image('avatar.jpeg');


            $response = $this->withHeaders([
                'Authorization' => 'Bearer  2|X9HITDuJaSPZurSSU2DrES1RaSLwM1t1CrjyyDkf',
            ])
                ->post(
                    '/book/create',
                    [
                        'name' => 'Book 1',
                        'writer' => 'Writer 1',
                        'publisher' => 'Publisher 1',
                        'brief' => 'required',
                        'start_page' => 1,
                        'end_page' => 200,
                        'link' => 'https://www.google.com/',
                        'section_id' => 2,
                        'type_id' => '6',
                        'image'=>$file,
                        'level'=>'level'
                    ]
                );

            $response->assertStatus(403);
        }

        public function test_user_with_permession_can_update_book()
            {

                $this->withoutExceptionHandling();
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
                ])
                    ->post(
                        '/book/update',
                        [
                            'book_id'=>'78',
                            'writer' => 'Writer Test'
                        ]
                    );
                $response->assertstatus(200);
        }

        public function test_user_with_no_permession_cannot_update_book()
            {

                $this->withoutExceptionHandling();
                $this->expectException(NotAuthorized::class);
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer 2|X9HITDuJaSPZurSSU2DrES1RaSLwM1t1CrjyyDkf',
                ])
                    ->post(
                        '/book/update',
                        [
                            'book_id'=>'50'
                        ]
                    );
                
                $response->assertstatus(403);
        }
        
        
        public function test_user_with_permission_can_show_Book()
        {
                $this->withoutExceptionHandling();

                $response = $this->withHeaders([
                    'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E ',
                ])->post('/book/show', ['book_id' => '20']);
                    
                $response->assertStatus(200);

            }
        
            public function test_user_with_no_permission_can_show_Book()
            {
                    $this->withoutExceptionHandling();
    
                    $response = $this->withHeaders([
                        'Authorization' => 'Bearer 2|X9HITDuJaSPZurSSU2DrES1RaSLwM1t1CrjyyDkf',
                    ])->post('/book/show', ['book_id' => '20']);
                        
                    $response->assertStatus(200);
    
                }
                //choose bookid from db to delted it
            
            public function test_user_with_permisson_can_delete_Exit_Book(){

                $this->withoutExceptionHandling();
                $response =$this->withHeaders([
                    'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
                ])
                    ->post('/book/delete',['book_id'=>'7']);
            

                $response->assertstatus(200);
            }
                //choose book id from database
        
            public function test_user_with_no_permisson_cannot_delete_Exit_Book(){

                $this->withoutExceptionHandling();
                $this->expectException(NotAuthorized::class);
                $response =$this->withHeaders([
                    'Authorization' => 'Bearer 2|X9HITDuJaSPZurSSU2DrES1RaSLwM1t1CrjyyDkf',
                ])
                    ->post('/book/delete',['book_id'=>'20']);
            

                $response->assertstatus(403);
            }

         
            
            public function test_user_with_permission_can_show_BookByType()
            {
                    $this->withoutExceptionHandling();
    
                    $response = $this->withHeaders([
                        'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
                    ])->post('/book/book-by-type', ['type_id' => '6']);
                        
                    $response->assertStatus(200);
    
                }
                   
                public function test_user_with_no_permission_can_show_BookByType()
                {
                        $this->withoutExceptionHandling();
        
                        $response = $this->withHeaders([
                            'Authorization' => 'Bearer 2|X9HITDuJaSPZurSSU2DrES1RaSLwM1t1CrjyyDkf',
                        ])->post('/book/book-by-type', ['type_id' => '6']);
                            
                        $response->assertStatus(200);
        
                    }

                public function test_user_with_permission_can_show_BookByLevel()
                {
                        $this->withoutExceptionHandling();
        
                        $response = $this->withHeaders([
                            'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
                        ])->post('/book/book-by-level', ['level' => 'level']);
                            
                        $response->assertStatus(200);
        
                    }
            
                 public function test_user_with_no_permission_can_show_BookByLevel()
                    {
                            $this->withoutExceptionHandling();
            
                            $response = $this->withHeaders([
                                'Authorization' => 'Bearer 2|X9HITDuJaSPZurSSU2DrES1RaSLwM1t1CrjyyDkf',
                            ])->post('/book/book-by-level', ['level' => 'level']);
                                
                            $response->assertStatus(200);
            
                        }
             public function test_user_with_permission_can_show_BookBySection()
                {
                        $this->withoutExceptionHandling();
        
                        $response = $this->withHeaders([
                            'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
                        ])->post('/book/book-by-section', ['section_id' => '1']);
                            
                        $response->assertStatus(200);
        
                    }


             public function test_user_with_no_permission_can_show_BookBySection()
                {
                        $this->withoutExceptionHandling();
        
                        $response = $this->withHeaders([
                            'Authorization' => 'Bearer 2|X9HITDuJaSPZurSSU2DrES1RaSLwM1t1CrjyyDkf',
                        ])->post('/book/book-by-section', ['section_id' => '1']);
                            
                        $response->assertStatus(200);
        
                    }
                    //using full name as database

              public function test_user_with_permission_can_show_Exist_BookByName()
                    {
                            $this->withoutExceptionHandling();
            
                            $response = $this->withHeaders([
                                'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
                            ])->post('/book/book-by-name', ['name' => 'Book']);
                                
                            $response->assertStatus(200);
            
                        }
                        //using full name as database
              public function test_user_with_no_permission_can_show_Exist_BookByName()
                    {
                            $this->withoutExceptionHandling();
            
                            $response = $this->withHeaders([
                                'Authorization' => 'Bearer 2|X9HITDuJaSPZurSSU2DrES1RaSLwM1t1CrjyyDkf',
                            ])->post('/book/book-by-name', ['name' => 'Book']);
                                
                            $response->assertStatus(200);
            
                        }

                     //using just some correct letters   
                        public function test_user_with_permission_can_show_matched_letter_Exist_BookByName()
                        {
                                $this->withoutExceptionHandling();
                
                                $response = $this->withHeaders([
                                    'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
                                ])->post('/book/book-by-name', ['name' => 'bo']);
                                    
                                $response->assertStatus(200);
                
                            }
    //try to serch for a wrong name diffrent from data base but 
             public function test_user_with_permission_cannot_show_non_ExitBookByName()
                            {
                                    $this->withoutExceptionHandling();
                    
                                    $response = $this->withHeaders([
                                        'Authorization' => 'Bearer 1|OGms6N5RhBJBqHI418aOYAvcA5qHPK8QTjvB6J8E',
                                    ])->post('/book/book-by-name', ['name' => 'read']);
                                        
                                    $response->assertStatus(403);
                    
                                }
                }
        
            
