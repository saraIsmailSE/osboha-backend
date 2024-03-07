<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Ramadan\RamadanDayController;
use Illuminate\Console\Command;

class CloseRamadanDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ramadan:closeDay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close the previous day and open the next day of Ramadan';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new RamadanDayController();
        $controller->closeDay();
        return 0;
    }
}
