<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\MarkController;
use Illuminate\Console\Command;

class AcceptSupport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:accept';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accept support for all the users who have supported but whose leader has not yet accepted the support after the modify timer has passed.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new MarkController();
        $controller->acceptSupportForAll();

        return 0;
    }
}
