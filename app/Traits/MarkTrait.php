<?php

namespace App\Traits;

use App\Models\Mark;
use Illuminate\Support\Facades\DB;

trait MarkTrait
{
    private function ambassadorWeekMark($user_id, $weekIds)
    {
        $weekIds= $weekIds->toArray();

        $response =  Mark::where('user_id',  $user_id)
            ->whereIn('week_id', $weekIds)
            ->with('thesis')
            ->with('thesis.book')
            ->with('thesis.comment')
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
            ->orderBy('marks.created_at', 'desc')
            ->get();
        foreach ($weekIds as $weekId) {
            $mark = $response->firstWhere('week_id', $weekId);
            if (!$mark) {
                $mark = [
                    'week_id' => $weekId,
                    'reading_mark' => 0,
                    'writing_mark' => 0,
                    'total_pages' => 0,
                    'total_thesis' => 0,
                    'total_screenshot' => 0,
                    'support' => 0,
                ];
            }
        
            $results[] = $mark;
        }
        
        return $results;    
        }
}
