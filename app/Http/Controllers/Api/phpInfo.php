<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class phpInfo extends Controller
{

    public function index()
    {
        return    phpinfo();
    }
}
