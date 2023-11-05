<?php

namespace App\Traits;

trait WeekTrait
{
    /**
     * search for week title based on the date of the week
     * @author Asmaa     
     * @param Date $date (date of biginning week), 
     * @param Array $year_weeks(array of year weeks dates and titles)
     * @return String title of the passed week date
     * @return Null if not found
     */
    private function search_for_week_title($date, $year_weeks)
    {
        foreach ($year_weeks as $val) {
            if ($val['date'] === $date) {
                return $val['title'];
            }
        }
        return null;
    }
}
