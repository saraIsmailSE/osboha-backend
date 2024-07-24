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
        $current_marathon = OsbohaMarthon::where('is_active', 1)->latest()->first();
        return $this->jsonResponseWithoutMessage($current_marathon, 'data', 200);
    }
    public function endMarathon($marathon_id)
    {
        if (Auth::user()->hasanyrole(['admin', 'marathon_coordinator'])) {
            $OsbohaMarthon = OsbohaMarthon::find($marathon_id);
            $OsbohaMarthon->update(['is_active' => 0]);
            MarathonWeek::where('osboha_marthon_id', $marathon_id)->update(['is_active' => 0]);

            return $this->jsonResponseWithoutMessage($OsbohaMarthon, 'data', 200);
        } else {
            throw new NotAuthorized();
        }
    }
    function createMarthon(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'marathon_title'                    => 'required_without:marathon_id',
            'marathon_id'        => 'required_without:marathon_title',
            'weeks_key' => 'required|array', // Validate that weeks_key is an array
            'weeks_key.*' => 'required|integer', // Validate that each element in the array is an integer
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if ($request->has('marathon_title') && $request->marathon_title != '') {
            $osboha_marthon = OsbohaMarthon::create(['title' => $request->marathon_title]);
            if ($osboha_marthon) {
                $osboha_marthon_id = $osboha_marthon->id;
            }
        } else if ($request->has('marathon_id')) {
            $osboha_marthon_id = $request->marathon_id;
        }
        if ($osboha_marthon_id) {
            $currentYear = date('Y');
            $requestedWeekKeys = $request->weeks_key;

            // Fetch existing MarathonWeek entries
            $existingMarathonWeeks = MarathonWeek::where('osboha_marthon_id', $osboha_marthon_id)->get();

            foreach ($requestedWeekKeys as $week_key) {
                $is_active = Week::where('week_key', $week_key)
                    ->whereYear('created_at', $currentYear)
                    ->exists() ? 1 : 0;

                MarathonWeek::updateOrCreate(
                    [
                        'osboha_marthon_id' => $osboha_marthon_id,
                        'week_key' => $week_key,
                    ],
                    [
                        'is_active' => $is_active
                    ]
                );
            }

            // Delete MarathonWeek entries that are not in the request
            foreach ($existingMarathonWeeks as $marathonWeek) {
                if (!in_array($marathonWeek->week_key, $requestedWeekKeys)) {
                    $marathonWeek->delete();
                }
            }
        }

        $marathon = OsbohaMarthon::find($osboha_marthon_id);
        return $this->jsonResponseWithoutMessage($marathon, 'data', 200);
    }

    public function show($id)
    {
        try {
            // Find the OsbohaMarthon by its ID
            $OsbohaMarthon = OsbohaMarthon::findOrFail($id);

            return $this->jsonResponseWithoutMessage($OsbohaMarthon, 'data', 200);
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e, 'data', 500);
        }
    }
}
