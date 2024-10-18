<?php

namespace Tests\Feature\Comment;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class NormalThesesTest extends ThesisTestsHelper
{
    //READ ONLY THESIS
    public function test_read_only_thesis_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getReadOnlyThesisRequest(1, 18);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(18, 0, 0, 30, 0));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_read_only_thesis_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getReadOnlyThesisRequest(1, 10);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(10, 0, 0, 20, 0));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_read_only_thesis_with_exceeding_max_pages_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getReadOnlyThesisRequest(1, 40);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(40, 0, 0, 50, 0));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //INCOMPLETE THESIS
    public function test_incomplete_thesis_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 18);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(18, 1, 0, 30, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_incomplete_thesis_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 10);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(10, 1, 0, 20, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //COMPLETE THESIS
    public function test_complete_thesis_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 18);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(18, 1, 0, 30, 24));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_complete_thesis_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 10);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(10, 1, 0, 20, 16));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //SCREENSHOT THESIS
    public function test_screenshot_thesis_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsThesisRequest(1, 18, 2);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(18, 0, 2, 30, 16));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_screenshot_thesis_with_normal_parts_and_exceeding_screenshots_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsThesisRequest(1, 18, 4);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(18, 0, 4, 30, 24));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_screenshot_thesis_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsThesisRequest(1, 10, 1);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(10, 0, 1, 20, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

    //Multiple READ_ONLY theses
    public function test_multiple_read_only_theses_with_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getReadOnlyThesisRequest(1, 6);
        $requestData2 = $this->getReadOnlyThesisRequest(7, 12);
        $requestData3 = $this->getReadOnlyThesisRequest(13, 18);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();
            $response3 = $this->postJson('api/v1/comments', $requestData3)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(18, 0, 0, 30, 0));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'), $response3->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //Multiple INCOMPLETE theses
    public function test_multiple_incomplete_theses_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 6);
        $requestData2 = $this->getIncompleteThesisRequest(7, 12);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(12, 2, 0, 20, 16));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_multiple_incomplete_theses_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 6);
        $requestData2 = $this->getIncompleteThesisRequest(7, 16);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(16, 2, 0, 30, 16));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //Multiple COMPLETE theses
    public function test_multiple_complete_theses_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 6);
        $requestData2 = $this->getCompleteThesisRequest(7, 12);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(12, 2, 0, 20, 16));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_multiple_complete_theses_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 6);
        $requestData2 = $this->getCompleteThesisRequest(7, 16);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(16, 2, 0, 30, 24));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //Multiple SCREENSHOT theses
    public function test_multiple_screenshot_theses_with_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsThesisRequest(1, 6, 1);
        $requestData2 = $this->getScreenshotsThesisRequest(7, 12, 1);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(12, 0, 2, 20, 16));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_multiple_screenshot_theses_with_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsThesisRequest(1, 6, 1);
        $requestData2 = $this->getScreenshotsThesisRequest(7, 15, 2);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(15, 0, 3, 30, 24));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //READ ONLY WITH INCOMPLETE THESIS
    public function test_read_only_with_incomplete_theses_and_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 3);
        $requestData2 = $this->getReadOnlyThesisRequest(4, 12);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(12, 1, 0, 20, 8));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_read_only_with_incomplete_theses_and_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 6);
        $requestData2 = $this->getReadOnlyThesisRequest(7, 21);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 1, 0, 40, 8));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //READ ONLY WITH COMPLETE THESIS
    public function test_read_only_with_complete_theses_and_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 6);
        $requestData2 = $this->getReadOnlyThesisRequest(7, 12);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(12, 1, 0, 20, 16));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_read_only_with_complete_theses_and_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 6);
        $requestData2 = $this->getReadOnlyThesisRequest(7, 21);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 1, 0, 40, 32));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //READ ONLY WITH SCREENSHOT THESIS
    public function test_read_only_with_screenshot_theses_and_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsThesisRequest(1, 6, 2);
        $requestData2 = $this->getReadOnlyThesisRequest(7, 12);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(12, 0, 2, 20, 16));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_read_only_with_screenshot_theses_and_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsThesisRequest(1, 6, 4);
        $requestData2 = $this->getReadOnlyThesisRequest(7, 21);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 0, 4, 40, 32));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //INCOMPLETE THESIS WITH COMPLETE THESIS
    public function test_incomplete_with_complete_theses_and_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 6);
        $requestData2 = $this->getCompleteThesisRequest(7, 12);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(12, 2, 0, 20, 16));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_incomplete_with_complete_theses_and_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 6);
        $requestData2 = $this->getCompleteThesisRequest(7, 15);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(15, 2, 0, 30, 24));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //INCOMPLETE THESIS WITH SCREENSHOT THESIS
    public function test_incomplete_with_screenshot_theses_and_normal_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 12);
        $requestData2 = $this->getScreenshotsThesisRequest(13, 18, 2);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(18, 1, 2, 30, 24));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_incomplete_with_screenshot_theses_and_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 12);
        $requestData2 = $this->getScreenshotsThesisRequest(13, 21, 5);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 1, 5, 40, 32));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //COMPLETE THESIS WITH SCREENSHOT THESIS
    public function test_complete_with_screenshot_theses_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 18);
        $requestData2 = $this->getScreenshotsThesisRequest(19, 40, 2);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(40, 1, 2, 50, 40));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //MIXED THESES
    public function test_mixed_theses_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 6);
        $requestData2 = $this->getScreenshotsThesisRequest(7, 12, 2);
        $requestData3 = $this->getReadOnlyThesisRequest(13, 18);
        $requestData4 = $this->getIncompleteThesisRequest(19, 26);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();
            $response3 = $this->postJson('api/v1/comments', $requestData3)->assertOk();
            $response4 = $this->postJson('api/v1/comments', $requestData4)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(26, 2, 2, 40, 32));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'), $response3->json('data.id'), $response4->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_normal_with_ramadan_theses_with_inactive_ramadan_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getThesisRequest(1, 18, null, $this->createScreenshots(3), 11); //normal
        $requestData2 = $this->getThesisRequest(1, 12, null, $this->createScreenshots(2), 160); //ramadan

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(30, 0, 5, 50, 40));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
