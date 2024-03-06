<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\RamadanDay;
use App\Models\RamadanHadith;
use App\Models\RamadanHadithMemorization;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class RamadanHadithMemorizationController extends Controller
{
    use ResponseJson;

    /**
     * @author Asmaa
     * Create a new submission for certain hadith or update existing submission
     * 
     * @param Request $request
     * @return ResponseJson
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ramadan_hadiths_id' => 'required|exists:ramadan_hadiths,id',
            'hadith' => 'required|confirmed',
            'redo' => 'nullable|boolean'
        ]);


        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', Response::HTTP_BAD_REQUEST);
        }

        //check if day is open
        $activeDay = RamadanDay::where('is_active', 1)->first();
        $hadith = RamadanHadith::where('id', $request->ramadan_hadiths_id)->whereHas('ramadanDay', function ($q) use ($activeDay) {
            $q->where('day', '<=', $activeDay->day);
        })->first();

        if (!$hadith) {
            return $this->jsonResponseWithoutMessage('لا يمكنك حفظ هذا الحديث الآن', 'data', Response::HTTP_BAD_REQUEST);
        }


        DB::beginTransaction();

        try {

            $hadithMemorization = RamadanHadithMemorization::updateOrCreate(
                ['ramadan_hadiths_id' => $request->ramadan_hadiths_id, 'user_id' => Auth::id()],
                [
                    'hadith' => $request->hadith,
                    'redo_at' => $request->redo ? now() : null,
                    'status' => 'pending',
                ]
            );

            DB::commit();

            $hadithMemorization->fresh();

            return $this->jsonResponseWithoutMessage($hadithMemorization, 'data', Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @author Asmaa
     * Get single hadith memorization for the authenticated user
     * 
     * @param int $hadithId
     * @param int $userId
     * @return ResponseJson
     */
    public function show($hadithId, $userId = null)
    {
        if ($userId) {
            if (!Auth::user()->hasRole('ramadan_hadith_corrector')) {
                return $this->jsonResponseWithoutMessage('لا تملك صلاحية التصحيح', 'data', Response::HTTP_FORBIDDEN);
            }
        } else {
            $userId = Auth::id();
        }

        $hadithMemorization = RamadanHadithMemorization::where('ramadan_hadiths_id', $hadithId)
            ->where('user_id', $userId)
            ->with('hadith')
            ->with('reviewer')
            ->first();

        if (!$hadithMemorization) {
            return $this->jsonResponseWithoutMessage('لا يوجد بيانات', 'data', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonResponseWithoutMessage($hadithMemorization, 'data', Response::HTTP_OK);
    }

    /**
     * @author Asmaa
     * Correct the hadith memorization
     * 
     * @param Request $request
     * @param int $hadithMemorizationId
     * @return ResponseJson
     */
    public function correct(Request $request, $hadithMemorizationId)
    {

        if (!Auth::user()->hasRole('ramadan_hadith_corrector')) {
            return $this->jsonResponseWithoutMessage('لا تملك صلاحية التصحيح', 'data', Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,redo',
            'reviews' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', Response::HTTP_BAD_REQUEST);
        }

        $hadithMemorization = RamadanHadithMemorization::find($hadithMemorizationId);

        if (!$hadithMemorization) {
            return $this->jsonResponseWithoutMessage('لا يوجد بيانات', 'data', Response::HTTP_NOT_FOUND);
        }

        $points = 0;
        if ($request->status == 'accepted') {
            if ($hadithMemorization->redo_at != null) {
                $points = 2;
            } else {
                $points = 3;
            }
        } else {
            $points = 1;
        }

        $hadithMemorization->update([
            'status' => $request->status,
            'points' => $points,
            'reviews' => $request->reviews,
            'reviewer_id' => Auth::id(),
        ]);

        return $this->jsonResponseWithoutMessage($hadithMemorization, 'data', Response::HTTP_OK);
    }

    /**
     * @author Asmaa
     * Get statistics for users participation in memorizing hadiths
     * 
     * @return ResponseJson
     */
    public function statistics()
    {
        /*count of users who memorized at least one hadith
        * count of users who memorized 5 hadiths
        * count of users who memorized 15 hadiths   
        * count of users who memorized 25 hadiths
        */

        $usersCount = RamadanHadithMemorization::select('user_id')
            ->distinct()
            ->count('user_id');

        $usersCount5 = RamadanHadithMemorization::select('user_id')
            ->distinct()
            ->where('status', 'accepted')
            ->havingRaw('count(user_id) = 5')
            ->count('user_id');

        $usersCount15 = RamadanHadithMemorization::select('user_id')
            ->distinct()
            ->where('status', 'accepted')
            ->havingRaw('count(user_id) = 15')
            ->count('user_id');

        $usersCount25 = RamadanHadithMemorization::select('user_id')
            ->distinct()
            ->where('status', 'accepted')
            ->havingRaw('count(user_id) = 25')
            ->count('user_id');

        $statistics = [
            'usersCount' => $usersCount,
            'usersCount5' => $usersCount5,
            'usersCount15' => $usersCount15,
            'usersCount25' => $usersCount25,
        ];

        return $this->jsonResponseWithoutMessage($statistics, 'data', Response::HTTP_OK);
    }

    /**
     * @author Asmaa
     * Get submitted hadiths by users
     * 
     * @return ResponseJson
     */
    public function getMemorizedHadiths()
    {
        if (!Auth::user()->hasRole('ramadan_hadith_corrector')) {
            return $this->jsonResponseWithoutMessage('لا تملك صلاحية التصحيح', 'data', Response::HTTP_FORBIDDEN);
        }

        $memorizedHadiths = RamadanHadithMemorization::
            // where('status', 'pending')
            // ->
            with('user')
            ->with('hadith')
            ->paginate(25);

        return $this->jsonResponseWithoutMessage([
            "hadiths" => $memorizedHadiths->items(),
            "total" => $memorizedHadiths->total(),
            "last_page" => $memorizedHadiths->lastPage(),
            "has_more_pages" => $memorizedHadiths->hasMorePages(),
        ], 'data', Response::HTTP_OK);
    }
}
