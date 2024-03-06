<?php

namespace App\Http\Controllers\Api\Ramadan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Carbon\Doctrine\CarbonDoctrineType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\RamadanAlwird;

class RamadanAlwirdController extends Controller
{
    
    /**
     * @author Rufaidah
     * store a number of juzu 
     * 
     * @param Request $request
     * @return ResponseJson
     */
    use ResponseJson;
    public function store(Request $request)
    {
        $points = 0;
        $validator = Validator::make($request->all(), [
            'number_juzu_read' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', Response::HTTP_BAD_REQUEST);
        }

        try {
            $user_id =5;

            if( $request->number_juzu_read >= 1)
            $points = 2;
            if( $request->number_juzu_read > 1)
            $points =$points + 4;
            if( $request->number_juzu_read > 3)
            $points =$points + 6;
        
            
            $ramadan_alwird = [
                'user_id' => $user_id,
                'ramadan_day_id' => $request->ramadan_day_id,
                'number_juzu_read' => $request->number_juzu_read,
                'points' => $points,
            ];

            $ramadan_alwird_day = RamadanAlwird::updateOrCreate(
                ['user_id' => $user_id, 'ramadan_day_id' => $request->ramadan_day_id],
                $ramadan_alwird
            );

            return $this->jsonResponseWithoutMessage($ramadan_alwird_day, 'data', 200);
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
        
    }
    public function statistics()
    {
        /*count of users who read at least one juzu
        * count of users who read more than one juzu
        * count of users who read 5 juzu or more    
        */

        $statistics = [];
        $ramadan_day_id = 1;
        
        // 1. Number of  users who read at least one juzu
        $statistics['num_users_read_one_juzu'] = RamadanAlwird::where('number_juzu_read', '=', 1)
            ->where('ramadan_day_id', 1)
            ->count();

        // 2. Number of users who read more than one juzu
        $statistics['num_users_read_more_than_one_juzu'] = RamadanAlwird::whereBetween('number_juzu_read', [2,4])
            ->where('ramadan_day_id', $ramadan_day_id)
            ->count();

        // 3. Number of users who read 5 juzu or more 
        $statistics['num_users_read_five_juzu_or_more'] = RamadanAlwird::where('number_juzu_read', '>', 4)
            ->where('ramadan_day_id', $ramadan_day_id)
            ->count();

        // 5. Summation of Auth user
        $statistics['auth_specific_ramadan_alwird_points'] = RamadanAlwird::where('user_id',5)
            ->where('ramadan_day_id', $ramadan_day_id)
            ->pluck('points');
            

        return $this->jsonResponseWithoutMessage($statistics, 'data', 200);


    }

   
}
