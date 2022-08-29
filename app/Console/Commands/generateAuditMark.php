<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class generateAuditMark extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:auditMark';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate audit mark';

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
       // Log::info("Hello" ); 
        app()->call('App\Http\Controllers\Api\MarkController@generateAuditMarks');
        \Log::info("Cron is working fine!");  
    }
}
