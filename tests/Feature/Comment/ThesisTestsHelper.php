<?php

namespace Tests\Feature\Comment;

use App\Models\Mark;
use App\Models\User;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ThesisTestsHelper extends TestCase
{
    protected $user;
    protected $week;

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

    protected function createWeek(): Week
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

    protected function getMark(int $userId, int $weekId): Mark
    {
        return Mark::where('user_id', $userId)->where('week_id', $weekId)->first();
    }

    protected function assertMark(Mark $mark, array $expectedMark)
    {
        $this->assertEquals($expectedMark['total_pages'], $mark->total_pages);
        $this->assertEquals($expectedMark['total_thesis'], $mark->total_thesis);
        $this->assertEquals($expectedMark['total_screenshot'], $mark->total_screenshot);
        $this->assertEquals($expectedMark['reading_mark'], $mark->reading_mark);
        $this->assertEquals($expectedMark['writing_mark'], $mark->writing_mark);
    }

    protected function getCurrentWeek(): Week
    {
        return Week::latest()->first();
    }

    protected function deleteThesis(int ...$ids)
    {
        foreach ($ids as $id) {
            $this->deleteJson("api/v1/comments/$id")->assertOk();
        }
    }

    protected function getAuditThesisRequest(int $thesisId, string $status, ?int $rejected_parts = null): array
    {
        return [
            'week_id' => $this->week->id,
            'modifier_reason_id' => 1,
            'thesis_id' => $thesisId,
            'status' => $status,
            'rejected_parts' => $rejected_parts
        ];
    }

    protected function getThesisRequest(int $startPage, int $endPage, ?string $body = null, ?array $screenshots = null, ?int $book_id = null): array
    {
        $data = [
            'book_id' => $book_id ?? 17,
            'start_page' => $startPage,
            'end_page' => $endPage,
            'type' => 'thesis',
        ];

        if ($body) {
            $data['body'] = $body;
        }

        if ($screenshots) {
            $data['screenShots'] = $screenshots;
        }

        return $data;
    }

    protected function getReadOnlyThesisRequest(int $startPage, int $endPage): array
    {
        return $this->getThesisRequest($startPage, $endPage);
    }

    protected function getIncompleteThesisRequest(int $startPage, int $endPage): array //less than 400 char
    {
        return $this->getThesisRequest($startPage, $endPage, 'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Est fugit assumenda ad vel deserunt consequuntur facilis quibusdam, nulla e');
    }

    protected function getCompleteThesisRequest(int $startPage, int $endPage): array //more than 400 char
    {
        return $this->getThesisRequest($startPage, $endPage, 'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Harum consectetur facilis recusandae corporis vitae incidunt quas minima debitis natus quae fuga temporibus, architecto ratione tempora sed rem quos illum. Magni esse quos eum. Modi repellat eveniet magni placeat pariatur non error autem iure, tempora porro ea qui corrupti praesentium debitis, voluptatum rerum unde doLorem, ipsum dolor sit amet consectetur adipisicing elit. Harum consectetur facilis recusandae corporis vitae incidunt quas minima debitis natus quae fuga temporibus, architecto ratione ');
    }

    protected function getScreenshotsThesisRequest(int $startPage, int $endPage, int $screenshotsCount): array
    {
        return $this->getThesisRequest($startPage, $endPage, null, $this->createScreenshots($screenshotsCount));
    }

    protected function getScreenshotsWithIncompleteThesisRequest(int $startPage, int $endPage, int $screenshotsCount): array
    {
        return $this->getThesisRequest($startPage, $endPage, 'Lorem ipsum, dolor sit amet consectetur adipi', $this->createScreenshots($screenshotsCount));
    }

    protected function createScreenshots(int $count): array
    {
        $screenshots = [];

        for ($i = 0; $i < $count; $i++) {
            $screenshots[] = UploadedFile::fake()->image(public_path('asset/images/verified.png'));
        }

        return $screenshots;
    }

    protected function getExpectedMark(int $totalPages, int $totalThesis, int $totalScreenshot, int $readingMark, int $writingMark): array
    {
        return [
            'total_pages' => $totalPages,
            'total_thesis' => $totalThesis,
            'total_screenshot' => $totalScreenshot,
            'reading_mark' => $readingMark,
            'writing_mark' => $writingMark,
        ];
    }
}
