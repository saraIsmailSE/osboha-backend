<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Ramadan\{
    RamadanDayController,
    RamadanNightPrayerController,
    RamadanGolenDayController,
    RamadanHadithController,
    RamadanHadithMemorizationController,
    RamadanQuranWirdController,
    RamadanQuestionAnswerController,
    RamadanQuestionController,
};

######## Start ramadan-day ########
Route::controller(RamadanDayController::class)
    ->prefix('ramadan-day')
    ->group(function () {
        Route::get('/all', 'all');
        Route::get('/current', 'currentDay');
        Route::get('/previous', 'previousDay');
        Route::get('/day-by-id/{id}', 'dayById');
    });
######## End ramadan-day ########


######## Start ramadan-golden-day ########
Route::controller(RamadanGolenDayController::class)
    ->prefix('ramadan-golden-day')
    ->group(function () {
        Route::post('/store', 'store');
        Route::get('/statistics/{ramadan_day_id}', 'statistics');
        Route::get('/show/{ramadan_day_id}', 'show');
    });
######## End ramadan-golden-day ########


######## Start ramadan-night-pray ########
Route::controller(RamadanNightPrayerController::class)
    ->prefix('ramadan-night-pray')
    ->group(function () {
        Route::post('/store', 'store');
        Route::get('/statistics/{ramadan_day_id}', 'statistics');
        Route::get('/show/{ramadan_day_id}', 'show');
    });
######## End ramadan-night-pray ########


######## Start ramadan-hadith-memorization ########
Route::controller(RamadanHadithMemorizationController::class)
    ->prefix('ramadan-hadith-memorization')
    ->group(function () {
        Route::post('/', 'create');
        Route::get('/show/{hadithMemorizationId}', 'show')->where('hadithMemorizationId', '[0-9]+');
        Route::post('/correct', 'correct');
        Route::get('/statistics/{ramadan_day_id}', 'statistics');
        Route::get('/pending', 'getMemorizedHadiths');
    });
######## End ramadan-hadith-memorization ########


######## Start ramadan-hadith ########
Route::controller(RamadanHadithController::class)
    ->prefix('ramadan-hadith')
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/days/{day_id}', 'getHadithByDay')->where('day_id', '[0-9]+');
        Route::get('/show/{id}', 'show')->where('day_id', '[0-9]+');
    });
######## End ramadan-hadith ########


######## Start ramadan-quran-wird ########
Route::controller(RamadanQuranWirdController::class)
    ->prefix('ramadan-quran-wird')
    ->group(function () {
        Route::post('/store', 'store');
        Route::get('/show/{ramadan_day_id}', 'show');
        Route::get('/statistics/{ramadan_day_id}', 'statistics');
    });
######## End ramadan-quran-wird ########


######## Start ramadan-question-answer ########
Route::controller(RamadanQuestionAnswerController::class)
    ->prefix('ramadan-question-answer')
    ->group(function () {
        Route::post('/store', 'store');
        Route::post('/correct', 'correctAnswer');
        Route::get('/show/{id}', 'show');
        Route::get('/get-pending-questions/{category}', 'getPending');
    });
######## End ramadan-question-answer ########


######## Start ramadan-question ########
Route::controller(RamadanQuestionController::class)
    ->prefix('ramadan-question')
    ->group(function () {
        Route::get('/day/{day_id}', 'getQuestionsByDay')->where('day_id', '[0-9]+');
        Route::get('/show/{id}', 'show')->where('day_id', '[0-9]+');
    });
######## End ramadan-question ########
