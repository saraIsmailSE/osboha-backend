<?php

namespace Tests\Feature;

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
    public function test_add_new_book_missed_fieldes()
    {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 1|PKQmrLQvLZUmYZ8cX6WXU44YcpI4RknzYhJQJ483',
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

    public function test_add_new_book_without_permission()
    {
         /**
        * @expectedException NotAuthorized
        */
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);
        Storage::fake('avatars');
        $file = UploadedFile::fake()->image('avatar.jpeg');


        $response = $this->withHeaders([
            'Authorization' => 'Bearer 2|zuU4iHoJvYByjKIrAPDRrn0Xkbj2wznyJjU8e54U',
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
}
