<?php


namespace App\Http\Controllers\Api\Eligible;

use App\Models\EligibleGeneralInformations;
use App\Models\EligibleQuestion;
use App\Models\EligibleThesis;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Week;
use App\Traits\ResponseJson;


class TeamStatisticsController extends Controller
{

  use ResponseJson;

  public function teamStatistics($week_id)
  {

    // $week = Week::find($week_id);
    if ($week_id == -1) {
      $week = Week::latest()->skip(1)->first();
    } else {
      $week = Week::find($week_id);
    }

    $response = [];
    $response['week'] = $week;
    $response['weeks'] = Week::latest('id')->limit(10)->get();

    $weekPlusSevenDays = $week->created_at->addDays(7);

    if (Auth::user()->hasRole('super_reviewer')) {
      $response['for_super'] = 'reviewer';

      $response['eligible_thesis'] =  EligibleThesis::whereNotNull('reviewer_id')
        ->where(function ($query) use ($week, $weekPlusSevenDays) {
          $query->where('updated_at', '>=', $week->created_at)
            ->where('updated_at', '<=', $weekPlusSevenDays);
        })
        ->get();
      $response['eligible_questions'] =  EligibleQuestion::whereNotNull('reviewer_id')
        ->where(function ($query) use ($week, $weekPlusSevenDays) {
          $query->where('updated_at', '>=', $week->created_at)
            ->where('updated_at', '<=', $weekPlusSevenDays);
        })
        ->get();
      $response['eligible_general_informations'] =  EligibleGeneralInformations::whereNotNull('reviewer_id')
        ->where(function ($query) use ($week, $weekPlusSevenDays) {
          $query->where('updated_at', '>=', $week->created_at)
            ->where('updated_at', '<=', $weekPlusSevenDays);
        })
        ->get();
    }
    if (Auth::user()->hasRole('super_auditer')) {
      $response['for_super'] = 'auditer';

      $response['eligible_thesis'] =  EligibleThesis::where('status', 'audited')
        ->where(function ($query) use ($week, $weekPlusSevenDays) {
          $query->where('updated_at', '>=', $week->created_at)
            ->where('updated_at', '<=', $weekPlusSevenDays);
        })
        ->get();
      $response['eligible_questions'] =  EligibleQuestion::where('status', 'audited')
        ->where(function ($query) use ($week, $weekPlusSevenDays) {
          $query->where('updated_at', '>=', $week->created_at)
            ->where('updated_at', '<=', $weekPlusSevenDays);
        })
        ->get();
      $response['eligible_general_informations'] =  EligibleGeneralInformations::where('status', 'audited')
        ->where(function ($query) use ($week, $weekPlusSevenDays) {
          $query->where('updated_at', '>=', $week->created_at)
            ->where('updated_at', '<=', $weekPlusSevenDays);
        })
        ->get();
    }
    return $this->jsonResponseWithoutMessage($response, 'data', 200);
  }
}
