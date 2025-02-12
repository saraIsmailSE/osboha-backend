<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Console\Command;

class deleteOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:deleteOldNotifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notifications older than 2 weeks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new NotificationController();
        $controller->deleteOldNotifications();

        return 0;
    }
}
