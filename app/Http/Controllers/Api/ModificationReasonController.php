<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\ModificationReason;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;

class ModificationReasonController extends Controller
{
    use ResponseJson;
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        //
    }

    public function getReasonsForLeader()
    {
        $modificationReasons = ModificationReason::where('level', 'leader')->get();
        if ($modificationReasons->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage($modificationReasons, 'data', 200);
        }
        throw new NotFound;
    }
}