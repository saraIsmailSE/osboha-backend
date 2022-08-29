<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\support\facades\DB;


class Sign_up extends Model
{
    use HasFactory;

    public function selectHighPriority($leader_condition,$ambassador_condition){
        $users = DB::table('leader_requests')
        ->join('users', 'leader_requests.leader_id', '=', 'users.id')
        ->join('high_priority_requests', 'leader_requests.id', '=', 'high_priority_requests.request_id')
        ->where('leader_requests.is_done', '=', 0 )
        ->whereIn('leader_requests.gender',$leader_condition)
        ->whereIn('users.gender',$ambassador_condition)
        ->select('leader_requests.*', 'users.gender')
        ->orderByDesc('high_priority_requests.id')
        ->limit(1)->get();
      
      return $users ;
           
	}//selectHighPriority
    
	public function selectSpecialCare($leader_condition,$ambassador_condition){
         $users = DB::table('leader_requests')
         ->join('users', 'leader_requests.leader_id', '=', 'users.id')
         ->where('leader_requests.current_team_count', '=', 2 )//just for test
         ->whereIn('leader_requests.gender',$leader_condition)
         ->whereIn('users.gender',$ambassador_condition)
         ->where('leader_requests.is_done', '=', 0 )
         ->select('leader_requests.*', 'users.gender')
         ->orderByDesc('leader_requests.created_at')
        ->limit(1)->get();

    return $users ;
	}//selectSpecialCare
    public function selectTeam($leader_condition,$ambassador_condition,$logical_operator = "=",$value = "0"){
        $users = DB::table('leader_requests')
         ->join('users', 'leader_requests.leader_id', '=', 'users.id')
         ->where('leader_requests.current_team_count', $logical_operator,$value )
         ->whereIn('leader_requests.gender',$leader_condition)
         ->whereIn('users.gender',$ambassador_condition)
         ->where('leader_requests.is_done', '=', 0 )
         ->select('leader_requests.*', 'users.gender')
         ->orderByDesc('leader_requests.created_at')
         ->limit(1)->get();
      return $users ;
	}//selectTeam
  public function selectTeam_between($leader_condition,$ambassador_condition,$value1,$value2){
      $users = DB::table('leader_requests')
       ->join('users', 'leader_requests.leader_id', '=', 'users.id')
       ->whereBetween('leader_requests.current_team_count', [$value1, $value2])
       ->whereIn('leader_requests.gender',$leader_condition)
       ->whereIn('users.gender',$ambassador_condition)
       ->where('leader_requests.is_done', '=', 0 )
       ->select('leader_requests.*', 'users.gender')
       ->orderByDesc('leader_requests.created_at')
       ->limit(1)->get();
   
    return $users ;
}//selectTeam
  public function countRequests($request_id)
	{
    return DB::table('users')
    ->where('request_id', '=', $request_id )
    ->count();

	}
  public function updateRequest( $request_id ) {
    DB::table('leader_requests')
    ->where('id', '=', $request_id )
    ->update(['is_done' => 1]);
	} //updateRequest
}
