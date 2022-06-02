<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\support\facades\DB;


class Sign_up extends Model
{
    use HasFactory;
    public function selectHighPriority($leader_gender,$ambassador_gender){
      if($leader_gender == 'any'){
        $users = DB::table('leader_requests')
        ->join('users', 'leader_requests.leader_id', '=', 'users.id')
        ->join('high_priority_requests', 'leader_requests.id', '=', 'high_priority_requests.request_id')
        ->where('leader_requests.is_done', '=', 0 )
        ->where('leader_requests.gender', '=', $ambassador_gender)
        ->where(function($query) use ($leader_gender) {
        $query->where('users.gender', '=', $leader_gender)
        ->orwhere('users.gender', '=', 'male')
        ->orwhere('users.gender', '=', 'female');})
        ->select('leader_requests.*', 'users.gender')
        ->orderByDesc('high_priority_requests.id')
        ->limit(1)->get();
      }
      else{
        $users = DB::table('leader_requests')
        ->join('users', 'leader_requests.leader_id', '=', 'users.id')
        ->join('high_priority_requests', 'leader_requests.id', '=', 'high_priority_requests.request_id')
        ->where('leader_requests.is_done', '=', 0 )
        ->where('users.gender', '=', $leader_gender )
        ->where(function($query) use ($ambassador_gender) {
          $query->where('leader_requests.gender', '=', $ambassador_gender)
          ->orwhere('leader_requests.gender', '=', 'any' );})
        ->select('leader_requests.*', 'users.gender')
        ->orderByDesc('high_priority_requests.id')
        ->limit(1)->get();
      }
      return $users ;
           
	}//selectHighPriority
    
	public function selectSpecialCare($leader_gender,$ambassador_gender){
    if($leader_gender == 'any'){
         $users = DB::table('leader_requests')
         ->join('users', 'leader_requests.leader_id', '=', 'users.id')
         ->where('leader_requests.current_team_count', '=', 40 )//just for test
         ->where('leader_requests.gender', '=', $ambassador_gender )
         ->where(function($query) use ($leader_gender) {
          $query->where('users.gender', '=', $leader_gender)
          ->orwhere('users.gender', '=', 'male')
          ->orwhere('users.gender', '=', 'female');})
         ->where('leader_requests.is_done', '=', 0 )
         ->select('leader_requests.*', 'users.gender')
         ->orderByDesc('leader_requests.created_at')
        ->limit(1)->get();
    }
    else{
      $users = DB::table('leader_requests')
         ->join('users', 'leader_requests.leader_id', '=', 'users.id')
         ->where('leader_requests.current_team_count', '=', 40 )//just for test
         ->where('leader_requests.is_done', '=', 0 )
         ->where('users.gender', '=', $leader_gender )
         ->where(function($query) use ($ambassador_gender) {
          $query->where('leader_requests.gender', '=', $ambassador_gender)
          ->orwhere('leader_requests.gender', '=', 'any' );})
         ->select('leader_requests.*', 'users.gender')
         ->orderByDesc('leader_requests.created_at')
         ->limit(1)->get();

    }
    return $users ;
	}//selectSpecialCare
    public function selectTeam($leader_gender,$ambassador_gender,$logical_operator = "=",$value = "0"){
      if($leader_gender == 'any'){
        $users = DB::table('leader_requests')
         ->join('users', 'leader_requests.leader_id', '=', 'users.id')
         ->where('leader_requests.current_team_count', $logical_operator,$value )
         ->where('leader_requests.gender', '=', $ambassador_gender )
         ->where(function($query) use ($leader_gender) {
          $query->where('users.gender', '=', $leader_gender)
          ->orwhere('users.gender', '=', 'male')
          ->orwhere('users.gender', '=', 'female');})
         ->where('leader_requests.is_done', '=', 0 )
         ->select('leader_requests.*', 'users.gender')
         ->orderByDesc('leader_requests.created_at')
         ->limit(1)->get();
      }
      else{
        $users = DB::table('leader_requests')
        ->join('users', 'leader_requests.leader_id', '=', 'users.id')
        ->where('leader_requests.current_team_count', $logical_operator,$value  )
        ->where('leader_requests.is_done', '=', 0 )
        ->where('users.gender', '=', $leader_gender )
        ->where(function($query) use ($ambassador_gender) {
          $query->where('leader_requests.gender', '=', $ambassador_gender)
          ->orwhere('leader_requests.gender', '=', 'any' );})
        ->select('leader_requests.*', 'users.gender')
        ->orderByDesc('leader_requests.created_at')
        ->limit(1)->get();
      }
      return $users ;
	}//selectTeam
  public function selectTeam_between($leader_gender,$ambassador_gender,$value1,$value2){
    if($leader_gender == 'any'){
      $users = DB::table('leader_requests')
       ->join('users', 'leader_requests.leader_id', '=', 'users.id')
       ->whereBetween('leader_requests.current_team_count', [$value1, $value2])
       ->where('leader_requests.gender', '=', $ambassador_gender )
       ->where(function($query) use ($leader_gender) {
        $query->where('users.gender', '=', $leader_gender)
        ->orwhere('users.gender', '=', 'male')
        ->orwhere('users.gender', '=', 'female');})
       ->where('leader_requests.is_done', '=', 0 )
       ->select('leader_requests.*', 'users.gender')
       ->orderByDesc('leader_requests.created_at')
       ->limit(1)->get();
    }
    else{
      $users = DB::table('leader_requests')
      ->join('users', 'leader_requests.leader_id', '=', 'users.id')
      ->whereBetween('leader_requests.current_team_count', [$value1, $value2])
      ->where('leader_requests.is_done', '=', 0 )
      ->where('users.gender', '=', $leader_gender )
      ->where(function($query) use ($ambassador_gender) {
        $query->where('leader_requests.gender', '=', $ambassador_gender)
        ->orwhere('leader_requests.gender', '=', 'any' );})
      ->select('leader_requests.*', 'users.gender')
      ->orderByDesc('leader_requests.created_at')
      ->limit(1)->get();
    }
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
