<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\UserExceptionController;
use Illuminate\Console\Command;

class finishedException extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userException:finished';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'change status to finished for finished user Exceptions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new UserExceptionController();
        $controller->endExceptions();

        return 0;
    }
}