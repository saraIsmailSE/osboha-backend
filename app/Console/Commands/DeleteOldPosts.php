<?php

namespace App\Console\Commands;

use App\Constants\PostConstants;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Week;
use App\Services\PostService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteOldPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old and not used posts';

    /**
     * Execute the console command.
     */
    public function handle(PostService $postService)
    {
        $chunkSize = 100;

        $previous_week = Week::orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->last();

        $date = $previous_week->created_at;
        Log::channel('Posts')->info("START Deleting old posts older than {$date}...");
        Log::channel('Posts')->info('================================================================================================================');

        $this->info("Deleting old posts older than {$date}...");

        $index = 0;
        $allStatistics = [
            'allDeleted' => 0,
            'allFailed' => 0,
        ];
        Post:: //where('id', 30130)
            whereIn('type_id', PostType::whereIn('type', [PostConstants::ANNOUNCEMENT_TYPE])->pluck('id'))
            ->where('created_at', '<', $date)
            ->orderBy('id')
            ->chunk($chunkSize, function ($posts) use ($postService, &$index, &$allStatistics) {
                $index++;

                $statistics = [
                    'deleted' => 0,
                    'failed' => 0,
                ];

                Log::channel('Posts')->info('START processing chunk ' . $index);
                Log::channel('Posts')->info('Chunk size: ' . count($posts));

                foreach ($posts as $post) {
                    if ($postService->deletePost($post)) {
                        $statistics['deleted']++;
                    } else {
                        $statistics['failed']++;
                        Log::channel('Posts')->error('Error while deleting post in chunk ' . $index . '. Post details: ', [
                            'id' => $post->id,
                        ]);
                    }
                }

                $allStatistics['allDeleted'] += $statistics['deleted'];
                $allStatistics['allFailed'] += $statistics['failed'];

                Log::channel('Posts')->info('FINISHED processing chunk ' . $index);
                Log::channel('Posts')->info('Chunk statistics: ', $statistics);
                Log::channel('Posts')->info('Total statistics so far: ', $allStatistics);
                Log::channel('Posts')->info('================================================================================================================');
            });
        Log::channel('Posts')->info("FINISHED deleting posts older than {$date}");
        Log::channel('Posts')->info('Total statistics: ', $allStatistics);
        $this->info("FINISHED deleting posts older than {$date}");
    }
}
