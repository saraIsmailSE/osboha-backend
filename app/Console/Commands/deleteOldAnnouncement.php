<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Week;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class deleteOldAnnouncement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:deleteOldAnnouncement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete posts of type announcement  older than 3 weeks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $previous_week = Week::orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->last();

        Post::whereHas('type', function ($q) {
            $q->where('type', '=', 'announcement');
        })
            ->where('created_at', '<', $previous_week->created_at)
            ->get()
            ->each(function ($post) {
                $post->delete();
            });
        return 0;
    }
}
