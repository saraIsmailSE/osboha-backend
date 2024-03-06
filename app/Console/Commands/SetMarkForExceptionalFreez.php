<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\UserExceptionController;
use Illuminate\Console\Command;

class SetMarkForExceptionalFreez extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exceptions:setMarkForExceptionalFreeze';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Mark For Exceptional Freez => is_freezed=1';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new UserExceptionController();
        $controller->SetMarkForExceptionalFreez();

        return 0;
    }
}
