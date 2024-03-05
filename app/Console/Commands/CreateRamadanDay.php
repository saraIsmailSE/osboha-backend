<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Ramadan\RamadanDayController;
use Illuminate\Console\Command;

class CreateRamadanDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ramadan:createDay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new day in Ramadan days table.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new RamadanDayController();
        $controller->create();
        return 0;
    }
}
