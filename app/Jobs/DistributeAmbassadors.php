<?php

namespace App\Jobs;

use App\Traits\AmbassadorsTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DistributeAmbassadors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AmbassadorsTrait;
    protected $requestId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->distributeAmbassadors($this->requestId);
    }
}
