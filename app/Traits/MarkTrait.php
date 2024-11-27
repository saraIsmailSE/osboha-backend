<?php

namespace App\Traits;

use App\Models\Mark;
use Illuminate\Support\Facades\DB;

trait MarkTrait
{
    private function ambassadorWeekMark($user_id, $weekIds)
    {
        $marks = Mark::where('user_id', $user_id)
        ->whereIn('week_id', $weekIds)
        ->with([
            'thesis' => function ($query) {
                $query->setEagerLoads([]);
                $query->with([
                    'book' => function ($query) {
                        $query->setEagerLoads([]);
                        $query->with('type');
                    },
                    'comment' => function ($query) {
                        $query->setEagerLoads([]);
                    },
                ]);
            },
        ])
        ->select([
            'marks.*',
            DB::raw('COALESCE(marks.id, 0) as marksId'),
            DB::raw('COALESCE(marks.reading_mark, 0) as reading_mark'),
            DB::raw('COALESCE(marks.writing_mark, 0) as writing_mark'),
            DB::raw('COALESCE(marks.total_pages, 0) as total_pages'),
            DB::raw('COALESCE(marks.total_thesis, 0) as total_thesis'),
            DB::raw('COALESCE(marks.total_screenshot, 0) as total_screenshot'),
            DB::raw('COALESCE(marks.support, 0) as support'),
        ])
        ->orderBy('week_id', 'desc')
        ->get();

        $weeks = DB::table('weeks')
            ->whereIn('id', $weekIds->toArray())
            ->get()
            ->keyBy('id');

        $existingWeekIds = $marks->pluck('week_id')->toArray();
        $missingWeekIds = array_diff($weekIds->toArray(), $existingWeekIds);

        foreach ($missingWeekIds as $missingWeekId) {
            $weekData = $weeks->get($missingWeekId);
            $marks->push((object)[
                'id' => 0,
                'user_id' => $user_id,
                'week_id' => $missingWeekId,
                'reading_mark' => 0,
                'writing_mark' => 0,
                'total_pages' => 0,
                'total_thesis' => 0,
                'total_screenshot' => 0,
                'support' => 0,
                'created_at' => null,
                'updated_at' => null,
                'week' => $weekData,
            ]);
        }

        $marks = $marks->sortByDesc('week_id')->values();

        return $marks;
    }
}
