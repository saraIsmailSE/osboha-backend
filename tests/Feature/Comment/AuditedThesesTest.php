<?php

namespace Tests\Feature\Comment;

use App\Models\Thesis;
use Illuminate\Support\Facades\DB;

class AuditedThesesTest extends ThesisTestsHelper
{

    public function test_accepted_thesis_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 18);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'accepted');

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);
            $this->assertMark($userMark, $this->getExpectedMark(18, 1, 0, 30, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_rejected_thesis_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 18);
        $requestData2 =   $this->getInCompleteThesisRequest(19, 30);

        // print_r($requestData);
        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'rejected');

            // print_r($auditThesisRequest);

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);
            $this->assertMark($userMark, $this->getExpectedMark(12, 1, 0, 20, 8));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_rejected_writing_thesis_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getIncompleteThesisRequest(1, 18);
        $requestData2 = $this->getScreenshotsThesisRequest(19, 30, 2);

        // print_r($requestData);
        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();
            $response2 = $this->postJson('api/v1/comments', $requestData2)->assertOk();

            $selectedResponse = $response2;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'rejected_writing');

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);
            $this->assertMark($userMark, $this->getExpectedMark(30, 1, 0, 50, 8));

            $this->deleteThesis($response->json('data.id'), $response2->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_accepted_one_thesis_with_complete_thesis_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 21);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'accepted_one_thesis');

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 1, 0, 40, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_accepted_one_thesis_with_incomplete_thesis_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getInCompleteThesisRequest(1, 21);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'accepted_one_thesis');

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 1, 0, 40, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_accepted_one_thesis_with_screenshots_thesis_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsThesisRequest(1, 21, 3);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'accepted_one_thesis');

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 0, 1, 40, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_accepted_one_thesis_with_thesis_and_screenshots_thesis_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsWithIncompleteThesisRequest(1, 21, 3);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'accepted_one_thesis');

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 1, 0, 40, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_rejected_parts_thesis_with_complete_thesis_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 21);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'rejected_parts', 1);

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 1, 0, 40, 24));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_rejected_parts_thesis_with_incomplete_thesis_and_screenshots_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getScreenshotsWithIncompleteThesisRequest(1, 21, 2);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'rejected_parts', 2);

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(21, 1, 2, 40, 8));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function test_rejected_parts_thesis_with_complete_thesis_and_exceeding_parts_mark()
    {
        $this->actingAs($this->user, 'web');

        $requestData = $this->getCompleteThesisRequest(1, 12);

        DB::beginTransaction();

        try {
            $response = $this->postJson('api/v1/comments', $requestData)->assertOk();

            $selectedResponse = $response;
            $auditThesisRequest = $this->getAuditThesisRequest($selectedResponse->json('data.thesis.id'), 'rejected_parts', 5);

            $this->postJson('api/v1/modified-theses', $auditThesisRequest)->assertOk();

            $userMark = $this->getMark($this->user->id, $this->week->id);

            $this->assertMark($userMark, $this->getExpectedMark(12, 1, 0, 20, 0));

            $this->deleteThesis($response->json('data.id'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
