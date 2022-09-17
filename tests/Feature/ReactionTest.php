<?php

namespace Tests\Feature;

use App\Exceptions\NotAuthorized;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    public function test_user_create_reaction_missed_fieldes(){

        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('/reaction/create', [
                 'post_id'=>'4',
                 'comment_id'=>'5',

        ]);
        $response->assertJsonMissingValidationErrors(['reaction_id']);

    }
    public function test_user_can_create_reaction_in_post() {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('reaction/create', [
                'reaction_id'=>'2',
                'post_id'=>'23'
        ]);
        $response->assertok();

    }

    public function test_user_can_create_reaction_in_comment() {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('reaction/create', [
                'reaction_id'=>'3',
                'comment_id'=>'2',

        ]);
        $response->assertok();

    }

    public function test_user_can_create_reaction_in_comment_has_media() {
        $this->withoutExceptionHandling();
        Storage::fake('imageCreate');
        $file = UploadedFile::fake()->image('comment.jpn');

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('reaction/create', [
                'reaction_id'=>'1',
                'comment_id'=>'23',
                'media'=> $file,
                'type'=> 'normale',

        ]);
        $response->assertok();

    }

    public function test_user_can_create_reaction_withAllReq() {
        $this->withoutExceptionHandling();
        Storage::fake('imageCreate');
        $file = UploadedFile::fake()->image('comment.jpn');

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('reaction/create', [
                'reaction_id'=>'2',
                'post_id'=>'4',
                'comment_id'=>'5',
                'media'=> $file,
                'type'=> 'replay',

        ]);
        $response->assertok();

    }

    public function test_admin_can_create_NewReaction() {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 5|9eeBQAxfpp5GuwgVdpak3wAxPRahZBSu4c6AvMsV',
        ])->post('reaction/create', [
                'reaction_id'=>'0',
                'post_id'=>'4',
                'comment_id'=>'5'
        ]);
        $response->assertok();
    }
    public function test_user_can_create_NewReaction_without_permission() {
        $this->withoutExceptionHandling();
        $this->expectException(NotAuthorized::class);

        $response = $this->withHeaders([
        'Authorization' => 'Bearer 4|PYLc0CFGlIx0l8O5B0lslhoOHVVY6KM9Mnf4WjKf',
        ])->post('reaction/create', [
                'reaction_id'=>'0',
                'post_id'=>'4',
                'comment_id'=>'5'
        ]);
        $response->assertStatus(403);

    }

    
    //test for update function

    
}    