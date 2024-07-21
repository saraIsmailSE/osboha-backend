<?php

namespace App\Http\Controllers\Api\Marathon;

use App\Exceptions\NotAuthorized;
use App\Http\Controllers\Controller;

use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotFound;
use App\Models\OsbohaMarthon;
use App\Models\MarathonWeek;
use App\Models\MarthonBonus;
use App\Models\Mark;
use App\Models\Thesis;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\MarathonWeeksResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;




class OsbohaMarathonController extends Controller
{
    use ResponseJson;

    public function getCurrentMarathon()
    {
        $current_marathon = OsbohaMarthon::where('is_active',1)->latest()->first();
        return $this->jsonResponseWithoutMessage($current_marathon, 'data', 200);
    }
    public function endMarathon($marathon_id)
    {
        if (Auth::user()->hasanyrole(['admin', 'marathon_coordinator'])) {
            OsbohaMarthon::where('id', $marathon_id)->update(['is_active' => 0]);
            MarathonWeek::where('osboha_marthon_id', $marathon_id)->update(['is_active' => 0]);

            return $this->jsonResponseWithoutMessage("End Marathon Successfully", 'data', 200);
        } else {
            throw new NotAuthorized();
        }
    }
}
