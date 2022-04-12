<?php

namespace App\libstats;

use Spatie\Stats\BaseStats;

class MarksStats extends BaseStats
{
    public function getName() : string{
        return 'marks';
    }
}