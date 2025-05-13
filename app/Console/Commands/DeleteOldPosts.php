<?php

namespace App\Console\Commands;

use App\Constants\PostConstants;
use App\Models\Post;
use App\Models\PostType;
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

        $date = now();
        Log::channel('posts')->info("Deleting old posts older than 30 days on {$date}...");
        $this->info("Deleting old posts older than 30 days on {$date}...");

        Post:: //where('id', 30130)
            whereIn('type_id', PostType::whereIn('type', [PostConstants::ANNOUNCEMENT_TYPE])->pluck('id'))
            ->whereDate('created_at', '<=', now()->subDays(30))
            ->orderBy('id')
            ->chunk($chunkSize, function ($posts) use ($postService) {
                foreach ($posts as $post) {
                    $postService->deletePost($post);
                }
            });
        Log::channel('posts')->info('Old posts deleted successfully.');
        $this->info('Old posts deleted successfully.');;
    }
}
