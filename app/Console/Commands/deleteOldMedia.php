<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\MediaController;
use Illuminate\Console\Command;

class deleteOldMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:deleteOld';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete media files older than 2 weeks from the public folder';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new MediaController();
        $controller->removeOldMedia();

        return 0;
    }
}
