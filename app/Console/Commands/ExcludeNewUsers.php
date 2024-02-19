<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\ExcludingUsersV2Controller;
use Illuminate\Console\Command;

class ExcludeNewUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:exclude_new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exclude new users from the system based on their marks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new ExcludingUsersV2Controller();
        $controller->excludeNewMembers();

        return 0;
    }
}
