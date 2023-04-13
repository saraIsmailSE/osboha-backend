<?php

namespace App\Http\Controllers;

use App\Models\ModificationReason;
use Illuminate\Http\Request;

class ModificationResonController extends Controller
{
    public function getReasonsForLeader()
    {
        $reasons = ModificationReason::where('level', 'leader')->get();
        return $this->jsonResponseWithoutMessage($reasons, 'data', 200);
    }
}