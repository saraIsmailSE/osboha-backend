<?php

namespace Tests\Feature\Comment;

use App\Models\Mark;
use App\Models\User;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class NormalThesesTest extends TestCase
{
    private $user;
    private $week;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'platform.admin@osboha.com')->first();

        if (!$this->user) {
            $this->fail('Admin user not found');
        }

        $week = $this->getCurrentWeek();

        $this->week = Carbon::now()->lessThan($week->main_timer) ? $week : $this->createWeek();
    }

    //READ ONLY THESIS
    public function test_read_only_thesis_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 18,
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertOk();

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 18,
            'total_thesis' => 0,
            'total_screenshot' => 0,
            'reading_mark' => 30,
            'writing_mark' => 0,
        ];

        $this->assertMark($userMark, $expectedMark);

        $this->deleteThesis($response->json('data.id'));
    }

    public function test_read_only_thesis_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 10,
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 10,
            'total_thesis' => 0,
            'total_screenshot' => 0,
            'reading_mark' => 20,
            'writing_mark' => 0,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_read_only_thesis_with_exceeding_max_pages_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 40,
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 40,
            'total_thesis' => 0,
            'total_screenshot' => 0,
            'reading_mark' => 50,
            'writing_mark' => 0,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    //INCOMPLETE THESIS
    public function test_incomplete_thesis_with_normal_parts_mark()
    {
        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 18,
            'body' => 'This is an incomplete thesis body', //incomplete thesis
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 18,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 30,
            'writing_mark' => 8,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_incomplete_thesis_with_exceeding_parts_mark()
    {
        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 10,
            'body' => 'This is an incomplete thesis body', //incomplete thesis
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 10,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 20,
            'writing_mark' => 8,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_incomplete_thesis_with_exceeding_max_pages_mark()
    {
        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 40,
            'body' => 'This is an incomplete thesis body', //incomplete thesis
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 40,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 50,
            'writing_mark' => 8,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    //COMPLETE THESIS
    public function test_complete_thesis_with_normal_parts_mark()
    {
        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 18,
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.', //complete thesis
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 18,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 30,
            'writing_mark' => 24,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_complete_thesis_with_exceeding_parts_mark()
    {
        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 10,
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.', //complete thesis
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 10,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 20,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_complete_thesis_with_exceeding_max_pages_mark()
    {
        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 40,
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.', //complete thesis
            'type' => 'thesis'
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 40,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 50,
            'writing_mark' => 40,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    //SCREENSHOT THESIS
    public function test_screenshot_thesis_with_normal_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 18,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 18,
            'total_thesis' => 0,
            'total_screenshot' => 2,
            'reading_mark' => 30,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_screenshot_thesis_with_normal_parts_and_exceeding_screenshots_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 18,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 18,
            'total_thesis' => 0,
            'total_screenshot' => 4,
            'reading_mark' => 30,
            'writing_mark' => 24,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_screenshot_thesis_with_exceeding_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 10,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 10,
            'total_thesis' => 0,
            'total_screenshot' => 1,
            'reading_mark' => 20,
            'writing_mark' => 8,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_screenshot_thesis_with_exceeding_parts_and_exceeding_screenshots_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 10,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 10,
            'total_thesis' => 0,
            'total_screenshot' => 3,
            'reading_mark' => 20,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    public function test_screenshot_thesis_with_exceeding_max_pages_and_incomplete_screenshots_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 40,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response = $this->postJson('api/v1/comments', $requestData);

        $response->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 40,
            'total_thesis' => 0,
            'total_screenshot' => 2,
            'reading_mark' => 50,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response->json('data.id'));
    }

    //Multiple READ_ONLY theses
    public function test_multiple_read_only_theses_with_normal_parts_mark()
    {
        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 12,
            'type' => 'thesis',
        ];

        $requestData3 = [
            'book_id' => 17,
            'start_page' => 13,
            'end_page' => 18,
            'type' => 'thesis',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);


        $response3 = $this->postJson('api/v1/comments', $requestData3);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 18,
            'total_thesis' => 0,
            'total_screenshot' => 0,
            'reading_mark' => 30,
            'writing_mark' => 0,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
        $this->deleteThesis($response3->json('data.id'));
    }

    public function test_multiple_read_only_theses_with_excceding_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 12,
            'type' => 'thesis',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 13,
            'end_page' => 21,
            'type' => 'thesis',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 21,
            'total_thesis' => 0,
            'total_screenshot' => 0,
            'reading_mark' => 40,
            'writing_mark' => 0,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //Multiple INCOMPLETE theses
    public function test_multiple_incomplete_theses_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'This is a test thesis 1',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 12,
            'type' => 'thesis',
            'body' => 'This is a test thesis 2',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 12,
            'total_thesis' => 2,
            'total_screenshot' => 0,
            'reading_mark' => 20,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    public function test_multiple_incomplete_theses_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'This is a test thesis 1',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 16,
            'type' => 'thesis',
            'body' => 'This is a test thesis 2',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 16,
            'total_thesis' => 2,
            'total_screenshot' => 0,
            'reading_mark' => 30,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //Multiple COMPLETE theses
    public function test_multiple_complete_theses_with_normal_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 12,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 12,
            'total_thesis' => 2,
            'total_screenshot' => 0,
            'reading_mark' => 20,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    public function test_multiple_complete_theses_with_excedding_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 16,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 16,
            'total_thesis' => 2,
            'total_screenshot' => 0,
            'reading_mark' => 30,
            'writing_mark' => 24,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //Multiple SCREENSHOT theses
    public function test_multiple_screenshot_theses_with_normal_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 12,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 12,
            'total_thesis' => 0,
            'total_screenshot' => 2,
            'reading_mark' => 20,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    public function test_multiple_screenshot_theses_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 15,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 15,
            'total_thesis' => 0,
            'total_screenshot' => 3,
            'reading_mark' => 30,
            'writing_mark' => 24,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //READ ONLY WITH INCOMPLETE THESIS
    public function test_read_only_with_incomplete_theses_and_normal_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 3,
            'type' => 'thesis',
            'body' => 'This is a test thesis 1',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 4,
            'end_page' => 12,
            'type' => 'thesis',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 12,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 20,
            'writing_mark' => 8,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    public function test_read_only_with_incomplete_theses_and_exceeding_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'This is a test thesis 1',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 21,
            'type' => 'thesis',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 21,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 40,
            'writing_mark' => 8,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //READ ONLY WITH COMPLETE THESIS
    public function test_read_only_with_complete_theses_and_normal_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 12,
            'type' => 'thesis',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 12,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 20,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    public function test_read_only_with_complete_theses_and_exceeding_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 21,
            'type' => 'thesis',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 21,
            'total_thesis' => 1,
            'total_screenshot' => 0,
            'reading_mark' => 40,
            'writing_mark' => 32,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //READ ONLY WITH SCREENSHOT THESIS
    public function test_read_only_with_screenshot_theses_and_normal_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 12,
            'type' => 'thesis',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 12,
            'total_thesis' => 0,
            'total_screenshot' => 2,
            'reading_mark' => 20,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    public function test_read_only_with_screenshot_theses_and_exceeding_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 21,
            'type' => 'thesis',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 21,
            'total_thesis' => 0,
            'total_screenshot' => 4,
            'reading_mark' => 40,
            'writing_mark' => 32,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //INCOMPLETE THESIS WITH COMPLETE THESIS
    public function test_incomplete_with_complete_theses_and_normal_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'This is a test thesis 1',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 12,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 12,
            'total_thesis' => 2,
            'total_screenshot' => 0,
            'reading_mark' => 20,
            'writing_mark' => 16,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    public function test_incomplete_with_complete_theses_and_exceeding_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'This is a test thesis 1',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 15,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 15,
            'total_thesis' => 2,
            'total_screenshot' => 0,
            'reading_mark' => 30,
            'writing_mark' => 24,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //INCOMPLETE THESIS WITH SCREENSHOT THESIS
    public function test_incomplete_with_screenshot_theses_and_normal_parts_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 12,
            'type' => 'thesis',
            'body' => 'This is a test thesis 1',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 13,
            'end_page' => 18,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 18,
            'total_thesis' => 1,
            'total_screenshot' => 2,
            'reading_mark' => 30,
            'writing_mark' => 24,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    public function test_incomplete_with_screenshot_theses_and_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 12,
            'type' => 'thesis',
            'body' => 'This is a test thesis 1',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 13,
            'end_page' => 21,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 21,
            'total_thesis' => 1,
            'total_screenshot' => 5,
            'reading_mark' => 40,
            'writing_mark' => 32,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //COMPLETE THESIS WITH SCREENSHOT THESIS
    public function test_complete_with_screenshot_theses_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 18,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 19,
            'end_page' => 40,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 40,
            'total_thesis' => 1,
            'total_screenshot' => 2,
            'reading_mark' => 50,
            'writing_mark' => 40,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
    }

    //MIXED THESES
    public function test_mixed_theses_mark()
    {

        //login the admin user
        $this->actingAs($this->user, 'web');

        //create a new wee
        $requestData1 = [
            'book_id' => 17,
            'start_page' => 1,
            'end_page' => 6,
            'type' => 'thesis',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam convallis enim nulla, ut pulvinar lorem rhoncus ac. Quisque egestas ligula quis leo sodales, non aliquam ligula vestibulum. Vestibulum tempus volutpat leo, eget tempor sem accumsan eget. Vivamus elementum porttitor ornare. Suspendisse id erat sollicitudin, convallis augue in, eleifend nibh. Mauris scelerisque velit ullamcorper justo fermentum facilisis. Maecenas posuere dui at leo suscipit efficitur. Integer semper tristique metus.',
        ];

        $requestData2 = [
            'book_id' => 17,
            'start_page' => 7,
            'end_page' => 12,
            'type' => 'thesis',
            'screenShots' => [
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
                UploadedFile::fake()->image(public_path('asset/images/verified.png')),
            ]
        ];

        $requestData3 = [
            'book_id' => 17,
            'start_page' => 13,
            'end_page' => 18,
            'type' => 'thesis',
        ];

        $requestData4 = [
            'book_id' => 17,
            'start_page' => 19,
            'end_page' => 26,
            'type' => 'thesis',
            'body' => 'This is a test thesis 4',
        ];

        $response1 = $this->postJson('api/v1/comments', $requestData1);


        $response2 = $this->postJson('api/v1/comments', $requestData2);

        $response3 = $this->postJson('api/v1/comments', $requestData3);

        $response4 = $this->postJson('api/v1/comments', $requestData4);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);
        $response4->assertStatus(200);

        $userMark = $this->getMark($this->user->id, $this->week->id);

        $expectedMark = [
            'total_pages' => 26,
            'total_thesis' => 2,
            'total_screenshot' => 2,
            'reading_mark' => 40,
            'writing_mark' => 32,
        ];

        $this->assertMark($userMark, $expectedMark);
        $this->deleteThesis($response1->json('data.id'));
        $this->deleteThesis($response2->json('data.id'));
        $this->deleteThesis($response3->json('data.id'));
        $this->deleteThesis($response4->json('data.id'));
    }

    private function createWeek(): Week
    {
        return Week::create([
            'title' => 'Test Week',
            //previous sunday at 14:00:00
            'created_at' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addHours(14),
            //next sunday at 13:59:59
            'main_timer' => Carbon::now()->endOfWeek(Carbon::SATURDAY)->addHours(13)->addMinutes(59)->addSeconds(59),
            'is_vacation' => 0
        ]);
    }

    private function getMark(int $userId, int $weekId): Mark
    {
        return Mark::where('user_id', $userId)->where('week_id', $weekId)->first();
    }

    private function assertMark(Mark $mark, array $expectedMark)
    {
        $this->assertEquals($expectedMark['total_pages'], $mark->total_pages);
        $this->assertEquals($expectedMark['total_thesis'], $mark->total_thesis);
        $this->assertEquals($expectedMark['total_screenshot'], $mark->total_screenshot);
        $this->assertEquals($expectedMark['reading_mark'], $mark->reading_mark);
        $this->assertEquals($expectedMark['writing_mark'], $mark->writing_mark);
    }

    private function getCurrentWeek(): Week
    {
        return Week::latest()->first();
    }

    private function deleteThesis(int $id)
    {
        $this->deleteJson("api/v1/comments/$id")->assertOk();
    }
}
