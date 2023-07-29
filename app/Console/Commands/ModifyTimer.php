<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\AuditMarkController;
use App\Http\Controllers\Api\WeekController;
use Illuminate\Console\Command;

class ModifyTimer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ModifyTimer:Week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Modify Timer';

    /**
     * Create a new command instance.
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
        $controller = new WeekController();
        $controller->set_modify_timer();

        return 0;
    }
}
