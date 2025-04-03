<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Marathon\{
    OsbohaMarathonController,
    MarathonPointsController
};


####################  Marathon  ####################
Route::controller(OsbohaMarathonController::class)->prefix('osboha-marathon')->group(function () {
    Route::get('/current-marathon', 'getCurrentMarathon');
    Route::get('/end-marathon/{marathon_id}', 'endMarathon');
    Route::get('/show/{marathon_id}', 'show');
    Route::post('/create_marthon', 'createMarthon');
});
Route::controller(MarathonPointsController::class)->prefix('marathon-points')->group(function () {
    Route::get('/get-marathon-points/{user_id}/{osboha_marthon_id}', 'getMarathonPoints');
    Route::get('/get-specific-marathon-week-points/{user_id}/{osboha_marthon_id}/{week_id}', 'getSpecificMarathonWeekPoints');
    Route::post('/add-bonus', 'addBonus');
    Route::post('/subtract-bonus', 'subtractPoints');
    Route::get('/get-points-bonus/{user_id}/{osboha_marthon_id}', 'getBonusPoints');
    Route::post('/points-deduction', 'pointsDeduction');
    Route::get('/get-violations-reasons', 'getViolationsReasons');
    Route::get('/get-violations/{user_id}/{osboha_marthon_id}', 'getMarathonUserViolations');
    Route::delete('/user-violation/{violation_id}', 'deleteViolation');
    Route::get('/group-marathon-points-export/{group_id}/{osboha_marthon_id}', 'GroupMarathonPointsExport');
});
#################### End  Marathon  ####################
