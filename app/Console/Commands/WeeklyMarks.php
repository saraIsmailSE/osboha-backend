<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\WeekController;
use App\Models\Mark;
use Illuminate\Console\Command;

class WeeklyMarks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:marks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert mark record for every user in the system weekly on Sunday at midnight';

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
        $controller = new WeekController();
        $controller->create();

        return 0;
    }
}