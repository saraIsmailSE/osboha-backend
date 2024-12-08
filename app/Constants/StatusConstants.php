<?php

namespace App\Constants;

class StatusConstants
{
    public const ACCEPTED_STATUS = 'accepted';
    public const REJECTED_STATUS = 'rejected';
    public const PENDING_STATUS = 'pending';
    public const FINISHED_STATUS = 'finished';
    public const CANCELED_STATUS = 'cancelled';
    public const REJECTED_WRITING_STATUS = 'rejected_writing';
    public const ACCEPTED_ONE_THESIS_STATUS = 'accepted_one_thesis';
    public const REJECTED_PARTS_STATUS = 'rejected_parts';
    public const FREEZE_THIS_WEEK_TYPE = 'تجميد الأسبوع الحالي';
    public const FREEZE_NEXT_WEEK_TYPE = 'تجميد الأسبوع القادم';
    public const EXCEPTIONAL_FREEZING_TYPE = 'تجميد استثنائي';
    public const EXAMS_MONTHLY_TYPE = 'نظام امتحانات - شهري';
    public const EXAMS_SEASONAL_TYPE = 'نظام امتحانات - فصلي';
    public const WITHDRAWN_TYPE = 'انسحاب مؤقت';
}
