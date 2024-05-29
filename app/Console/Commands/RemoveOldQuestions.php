<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\GeneralConversationController;
use Illuminate\Console\Command;

class RemoveOldQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:removeOld';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove solved questions older than one month';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new GeneralConversationController();
        $controller->removeOldQuestions();
        return 0;
    }
}
