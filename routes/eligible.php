<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Eligible\{
    EligibleUserBookController,
    EligibleThesisController,
    EligibleQuestionController,
    EligibleCertificatesController,
    EligibleGeneralInformationsController,
    TeamStatisticsController
};


######## Start eligible-userbook ########
Route::controller(EligibleUserBookController::class)
    ->prefix('eligible-userbook')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/status/{status}', 'getUserBookByStatus');
        Route::patch('/status/{id}', 'changeStatus');
        Route::get('/last-achievement', 'lastAchievement');
        Route::get('/finished-achievement', 'finishedAchievement');
        Route::get('/count', 'checkOpenBook');
        Route::get('/certificate/{id}', 'checkCertificate');
        Route::get('/statistics/{id}', 'getStatistics');
        Route::get('/general-statistics', 'getGeneralstatistics');
        Route::get('/by-book-id/{bookId}', 'getByBookID');
        Route::get('/stage-status/{id}', 'getStageStatus');
        Route::get('/{id}', 'getById');
        Route::patch('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/review', 'review');
        Route::get('/ready/to', 'readyToAudit');
        Route::get('/check-achievement/{id}', 'checkAchievement');
        Route::get('/get-books/audit-status', 'getEligibleUserBooksWithAuditStatus');
        Route::get('/get-books/retard-status', 'getBooksWithRetardStatus');
        Route::patch('/undo/retard/{eligibleUserBookId}', 'undoRetard');
    });
######## End eligible-userbook ########

######## Start eligible-theses ########
Route::controller(EligibleThesisController::class)
    ->prefix('eligible-theses')
    ->group(function () {
        Route::get('/image', 'image');
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('final-degree/{id}', 'finalDegree');
        Route::get('by-status/{status}', 'getByStatus');
        Route::get('/photo-count/{id}', 'getThesisPhotosCount');
        Route::get('/{id}', 'show');
        Route::patch('update-photo/{id}', 'updatePhoto');
        Route::patch('review-thesis/{id}', 'reviewThesis');
        Route::patch('/{id}', 'update');
        Route::delete('/photo/{id}', 'deletePhoto');
        Route::delete('/{id}', 'destroy');
        Route::patch('add-degree/{id}', 'addDegree');
        Route::post('update-photo', 'updatePicture');
        Route::post('upload/{id}', 'uploadPhoto');
        Route::get('eligible_user_books_id/{user_book_id}&{status?}', 'getByUserBook');
        Route::get('book/{book_id}', 'getByBook');
        Route::post('/review', 'review');
        Route::patch('/undo/accept/{thesisId}', 'undoAccept');
        //April
        Route::get('get-theses-for-eligible-book/{eligible_user_books_id}', 'getThesesForEligibleBook');

    });
######## End eligible-theses ########

######## Start eligible-questions ########
Route::controller(EligibleQuestionController::class)
    ->prefix('eligible-questions')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('status/{status}', 'getByStatus');
        Route::get('user-book/{id}', 'getUserBookQuestions');
        Route::get('/{id}', 'show');
        Route::patch('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::patch('add-degree/{id}', 'addDegree');
        Route::get('book/{book_id}', 'getByBook');
        Route::get('final-degree/{id}', 'finalDegree');
        Route::get('user_book_id/{user_book_id}', 'getByUserBook');
        Route::get('status/{status}', 'getByStatus');
        Route::post('/review', 'review');
        Route::patch('review-question/{id}', 'reviewQuestion');
        Route::patch('/undo/accept/{questionId}', 'undoAccept');
        Route::get('get-questions-for-eligible-book/{eligible_user_books_id}', 'getQuestionsForEligibleBook');

    });
######## End eligible-questions ########
######## Start eligible-certificates ########
Route::controller(EligibleCertificatesController::class)
    ->prefix('eligible-certificates')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/user', 'getUserCertificates');
        Route::get('/{id}', 'show');
        Route::get('/full-certificate/{user_book_id}', 'fullCertificate');
        Route::patch('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
######## End eligible-certificates ########
######## Start eligible-general-informations ########
Route::controller(EligibleGeneralInformationsController::class)
    ->prefix('eligible-general-informations')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/user_book_id/{user_book_id}', 'getByUserBookId');
        Route::get('/{id}', 'show');
        Route::patch('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::patch('add-degree/{id}', 'addDegree');
        Route::get('book/{book_id}', 'getByBook');
        Route::get('final-degree/{id}', 'finalDegree');
        Route::get('user_book_id/{user_book_id}', 'getByUserBook');
        Route::get('status/{status}', 'getByStatus');
        Route::post('/review', 'review');
        Route::patch('review-general-informations/{id}', 'reviewGeneralInformations');
        Route::get('get-general-informations-for-eligible-book/{eligible_user_books_id}', 'getGeneralInformationsForEligibleBook');

    });
######## End eligible-general-informations ########
######## Start eligible-statistics ########
Route::controller(TeamStatisticsController::class)
    ->prefix('eligible-statistics')
    ->group(function () {
        Route::get('/my-team/{week_id}', 'teamStatistics');
    });
######## End eligible-statistics ########
