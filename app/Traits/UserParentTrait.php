<?php

namespace App\Traits;

use App\Models\UserParent;
use App\Models\Week;

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
}
