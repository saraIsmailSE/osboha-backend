<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\BookStatistics;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;

class BookStatisticsController extends Controller
{
    use ResponseJson;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $stat = BookStatistics::latest()->get();
        
        if($stat->isNotEmpty()){
            return $this->jsonResponseWithoutMessage($stat, 'data',200);
        }
        else{
            throw new NotFound();
        }
    }
}
