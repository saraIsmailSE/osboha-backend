<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookStatisticsResource;
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
            return $this->jsonResponseWithoutMessage(BookStatisticsResource::collection($stat), 'data',200);
        }
        else{
            throw new NotFound();
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BookStatistics  $bookStatistics
     * @return \Illuminate\Http\Response
     */
    public function show(BookStatistics $bookStatistics)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BookStatistics  $bookStatistics
     * @return \Illuminate\Http\Response
     */
    public function edit(BookStatistics $bookStatistics)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BookStatistics  $bookStatistics
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BookStatistics $bookStatistics)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BookStatistics  $bookStatistics
     * @return \Illuminate\Http\Response
     */
    public function destroy(BookStatistics $bookStatistics)
    {
        //
    }
}
