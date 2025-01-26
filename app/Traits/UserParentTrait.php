<?php

namespace App\Traits;

use App\Models\UserParent;
use App\Models\Week;
use Illuminate\Support\Facades\DB;

trait UserParentTrait
{

    function childrensByWeek($parent_id, $week_id, $roles)
    {
        $week = Week::find($week_id);
        $weekPlusSevenDays = $week->created_at->addDays(7);

        return UserParent::with('child.roles')
            ->whereHas('child.roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);
            })
            ->where(function ($query) use ($week, $weekPlusSevenDays, $parent_id) {
                $query->where(function ($q) use ($week, $weekPlusSevenDays, $parent_id) {
                    $q->where('created_at', '<=', $week->created_at)
                        ->where('updated_at', '>=', $weekPlusSevenDays)
                        ->where('parent_id', $parent_id)
                        ->where('is_active', 0);
                })
                    ->orWhere(function ($q) use ($parent_id) {
                        $q->where('parent_id', $parent_id)
                            ->where('is_active', 1);
                    });
            })
            ->get();
    }

    function nestedUsers($parentId)
    {
        $depthLimit = 3; // The number of tree levels

        $query = "
            WITH RECURSIVE cte AS (
                SELECT id, name, last_name, parent_id, 0 AS depth
                FROM users
                WHERE parent_id = :parentId

                UNION ALL

                SELECT u.id, u.name, u.last_name, u.parent_id, cte.depth + 1
                FROM cte
                JOIN users u ON u.parent_id = cte.id
                WHERE cte.depth < :depthLimit
            )
            SELECT * FROM cte
            ORDER BY depth, parent_id, id;
        ";

        return DB::select($query, [
            'parentId' => $parentId,
            'depthLimit' => $depthLimit - 1
        ]);
    }
}
