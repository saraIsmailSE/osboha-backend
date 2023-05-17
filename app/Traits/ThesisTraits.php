<?php

namespace App\Traits;

use App\Exceptions\NotFound;
use App\Http\Resources\ThesisResource;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Mark;
use App\Models\ModificationReason;
use App\Models\Thesis;
use App\Models\ThesisType;
use App\Models\User;
use App\Models\UserBook;
use App\Models\UserException;
use App\Models\Week;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait ThesisTraits
{
    use ResponseJson;

    ##########ASMAA##########
    public function __construct()
    {
        if (!defined('MAX_PARTS'))
            define('MAX_PARTS', 5);

        if (!defined('MAX_SCREENSHOTS'))
            define('MAX_SCREENSHOTS', 5);

        if (!defined('MAX_AYAT'))
            define('MAX_AYAT', 5);

        if (!defined('COMPLETE_THESIS_LENGTH'))
            define('COMPLETE_THESIS_LENGTH', 400);

        if (!defined('PART_PAGES'))
            define('PART_PAGES', 6);

        if (!defined('RAMADAN_PART_PAGES'))
            define('RAMADAN_PART_PAGES', 3);

        if (!defined('MIN_VALID_REMAINING'))
            define('MIN_VALID_REMAINING', 3);

        if (!defined('INCREMENT_VALUE'))
            define('INCREMENT_VALUE', 1);

        if (!defined('NORMAL_THESIS_TYPE'))
            define('NORMAL_THESIS_TYPE', 'normal');

        if (!defined('RAMADAN_THESIS_TYPE'))
            define('RAMADAN_THESIS_TYPE', 'ramadan');

        if (!defined('TAFSEER_THESIS_TYPE'))
            define('TAFSEER_THESIS_TYPE', 'tafseer');

        /*
        * Full mark out of 100 = reading_mark + writing mark + support
        * Full mark out of 90 = reading_mark + writing mark
        */
    }

    /**
     * check if the date belongs to the current week
     * @author Asmaa
     * @param Date $date
     * @return boolean
     */
    public function checkDateBelongsToCurrentWeek($mainTimer)
    {
        //check if now is less than the main timer of the week
        if (Carbon::now()->lessThan($mainTimer)) {
            return true;
        }
        return false;
    }

    /**
     * create new thesis
     * @author Asmaa
     * @todo check if the week exists and it is the current week
     * @todo check if the week is vacation or not and create mark record if it is vacation
     * @todo get thesis type
     * @todo calculate reading mark and writing mark for normal thesis or ramadan/tafseer thesis
     * @todo check if the marks exceed the maximum marks or not
     * @todo create new thesis
     * @todo update mark record
     * @param Array $thesis
     * @return jsonResponse
     */
    public function createThesis($thesis, $seeder = false)
    {
        $mark_record = null;
        if (!$seeder) {
            //asmaa - check if the week is existed or not
            $week = Week::latest('id')->first();

            if (!$this->checkDateBelongsToCurrentWeek($week->main_timer)) {
                // return $this->jsonResponseWithoutMessage('Cannot add thesis', 'data', 500);
                throw new \Exception('Cannot add thesis');
            }

            $week_id = $week->id;
            $mark_record = Mark::where('user_id', Auth::id())
                ->where('week_id', $week_id)
                ->first();

            //asmaa - check if the week is vacation or not and create mark record if it is vacation
            if ($week->is_vacation == 1 && !$mark_record) {
                $mark_record = Mark::create([
                    'user_id' => Auth::id(),
                    'week_id' => $week_id,
                ]);
            }
        } else {
            $mark_record = Mark::find($thesis['mark_id']);
        }

        if ($mark_record) {
            //get thesis type
            $thesis_type = ThesisType::find($thesis['type_id'])->first()->type;

            $max_length = (array_key_exists('max_length', $thesis) ? $thesis['max_length'] : 0);
            $total_thesis = (array_key_exists('max_length', $thesis) ? ($thesis['max_length'] > 0 ? INCREMENT_VALUE : 0) : 0);
            $total_screenshots = (array_key_exists('total_screenshots', $thesis) ? $thesis['total_screenshots'] : 0);
            $thesis_mark = 0;

            $thesis_data_to_insert = array(
                'comment_id'        => $thesis['comment_id'],
                'book_id'           => $thesis['book_id'],
                'mark_id'           => $mark_record->id,
                'user_id'           => $seeder ? $thesis['user_id'] : Auth::id(),
                'type_id'           => $thesis['type_id'],
                'start_page'        => $thesis['start_page'],
                'end_page'          => $thesis['end_page'],
                'max_length'        => $max_length,
                'total_screenshots' => $total_screenshots,
            );

            $thesisTotalPages = $thesis['end_page'] - $thesis['start_page'] > 0 ? $thesis['end_page'] - $thesis['start_page'] + 1 : 0;
            $mark_data_to_update = array(
                'total_pages'      => $mark_record->total_pages + $thesisTotalPages,
                'total_thesis'     => $mark_record->total_thesis + $total_thesis,
                'total_screenshot' => $mark_record->total_screenshot + $total_screenshots,
                'is_freezed'       => 0,
            );

            $reading_mark = $mark_record->reading_mark;
            $writing_mark = $mark_record->writing_mark;

            if (strtolower($thesis_type) === NORMAL_THESIS_TYPE) { //calculate mark for normal thesis or not completed ramadan/tafseer thesis                    
                $thesis_mark = $this->calculate_mark_for_normal_thesis(
                    $thesisTotalPages,
                    $max_length,
                    $total_screenshots,

                );
            } else if (
                strtolower($thesis_type) === RAMADAN_THESIS_TYPE ||
                strtolower($thesis_type) === TAFSEER_THESIS_TYPE
            ) { ///calculate mark for ramadan or tafseer thesis             

                $thesis_mark = $this->calculate_mark_for_ramadan_thesis(
                    $thesisTotalPages,
                    $max_length,
                    $total_screenshots,
                    (strtolower($thesis_type) === RAMADAN_THESIS_TYPE ? RAMADAN_THESIS_TYPE : TAFSEER_THESIS_TYPE),

                );
            }
            $reading_mark += $thesis_mark['reading_mark'];
            $writing_mark += $thesis_mark['writing_mark'];

            if ($reading_mark > config('constants.FULL_READING_MARK')) {
                $reading_mark = config('constants.FULL_READING_MARK');
            }

            if ($writing_mark > config('constants.FULL_WRITING_MARK')) {
                $writing_mark = config('constants.FULL_WRITING_MARK');
            }

            $mark_data_to_update['reading_mark'] = $reading_mark;
            $mark_data_to_update['writing_mark'] = $writing_mark;

            //update status to accepted if the thesis is read only
            if ($thesisTotalPages > 0 && $max_length == 0 && $total_screenshots == 0) {
                $thesis_data_to_insert['status'] = config('constants.ACCEPTED_STATUS');
            }

            $thesis = Thesis::create($thesis_data_to_insert);

            if ($thesis) {
                $this->createOrUpdateUserBook($thesis);
                $mark_record->update($mark_data_to_update);
                return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis added successfully!');
            } else {
                // return $this->jsonResponseWithoutMessage('Cannot add thesis', 'data', 500);
                throw new \Exception('Cannot add thesis');
            }
        } else {
            throw new NotFound;
        }
    }

    /**
     * update thesis
     * @author Asmaa
     * @todo get thesis based on comment id
     * @todo check if the week exists and it is the current week
     * @todo calculate reading and writing marks from the old thesis and the new thesis
     * @todo check if the marks exceed the full marks or not
     * @todo update mark record
     * @todo update thesis record
     * @param Array $thesisToUpdate
     * @return jsonResponse
     */
    public function updateThesis($thesisToUpdate)
    {
        $thesis = Thesis::where('comment_id', $thesisToUpdate['comment_id'])->first();

        $total_pages = $thesisToUpdate['end_page'] - $thesisToUpdate['start_page'] > 0 ? $thesisToUpdate['end_page'] - $thesisToUpdate['start_page'] + 1 : 0;

        if ($thesis) {
            $week = Week::latest('id')->first();

            if (!$this->checkDateBelongsToCurrentWeek($week->main_timer)) {
                // return $this->jsonResponseWithoutMessage('Cannot update thesis', 'data', 500);
                throw new \Exception('Cannot update thesis');
            }

            $week_id = $week->id;

            $mark_record = Mark::where('id', $thesis->mark_id)
                ->where('user_id', Auth::id())
                ->where('week_id', $week_id)
                ->first();

            if ($mark_record) {
                //get thesis type
                $thesis_type = ThesisType::find($thesis['type_id'])->first()->type;

                $max_length = ($thesisToUpdate['max_length'] ? $thesisToUpdate['max_length'] : 0);
                $total_thesis = ($thesisToUpdate['max_length'] ? ($thesisToUpdate['max_length'] > 0 ? INCREMENT_VALUE : 0) : 0);
                $total_screenshots = ($thesisToUpdate['total_screenshots'] ? $thesisToUpdate['total_screenshots'] : 0);

                $oldThesisTotalPages = $thesis->end_page - $thesis->start_page > 0 ? $thesis->end_page - $thesis->start_page + 1 : 0;

                $thesis_mark = 0;
                $old_thesis_mark = 0;

                $thesis_data_to_update = array(
                    'total_pages'       => $total_pages,
                    'max_length'        => $max_length,
                    'total_screenshots' => $total_screenshots,
                    'start_page'        => $thesisToUpdate['start_page'],
                    'end_page'          => $thesisToUpdate['end_page'],
                );

                if (strtolower($thesis_type) === NORMAL_THESIS_TYPE) { //calculate mark for normal thesis                     
                    $thesis_mark = $this->calculate_mark_for_normal_thesis(
                        $total_pages,
                        $max_length,
                        $total_screenshots,

                    );
                    //calculate the old mark to remove it from the total                        
                    $old_thesis_mark = $this->calculate_mark_for_normal_thesis(
                        $oldThesisTotalPages,
                        $thesis->max_length,
                        $thesis->total_screenshots,

                    );
                } else if (
                    strtolower($thesis_type) === RAMADAN_THESIS_TYPE ||
                    strtolower($thesis_type) === TAFSEER_THESIS_TYPE
                ) { ///calculate mark for ramadan or tafseer thesis             
                    $thesis_mark = $this->calculate_mark_for_ramadan_thesis(
                        $total_pages,
                        $max_length,
                        $total_screenshots,
                        $thesis_type,

                    );

                    $old_thesis_mark = $this->calculate_mark_for_ramadan_thesis(
                        $oldThesisTotalPages,
                        $thesis->max_length,
                        $thesis->total_screenshots,
                        $thesis_type,

                    );
                }

                $reading_mark = $thesis_mark['reading_mark'] + $mark_record->reading_mark - $old_thesis_mark['reading_mark'];
                $writing_mark = $thesis_mark['writing_mark'] + $mark_record->writing_mark - $old_thesis_mark['writing_mark'];

                if ($reading_mark > config('constants.FULL_READING_MARK')) {
                    $reading_mark = config('constants.FULL_READING_MARK');
                }

                if ($writing_mark > config('constants.FULL_WRITING_MARK')) {
                    $writing_mark = config('constants.FULL_WRITING_MARK');
                }

                $mark_data_to_update = array(
                    'total_pages'      => $mark_record->total_pages - $oldThesisTotalPages + $total_pages,
                    'total_thesis'     => $mark_record->total_thesis - ($thesis->max_length > 0 ? INCREMENT_VALUE : 0) + $total_thesis,
                    'total_screenshot' => $mark_record->total_screenshot - $thesis->total_screenshots + $total_screenshots,
                    'reading_mark' => $reading_mark,
                    'writing_mark' => $writing_mark,
                );

                $thesis->update($thesis_data_to_update);
                $mark_record->update($mark_data_to_update);
                $this->createOrUpdateUserBook($thesis);

                return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis updated successfully!');
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotFound;
        }
    }

    /**
     * delete thesis
     * @author Asmaa
     * @todo 
     * @todo get thesis based on comment id
     * @todo get comment related to the thesis
     * @todo check if the week exists and it is the current week
     * @todo delete thesis
     * @todo delete comment
     * @todo calculate marks for the rest of the thesis in the same week
     * @todo update mark record
     * @param Array $thesisToDelete
     * @return jsonResponse
     */
    public function deleteThesis($thesisToDelete)
    {
        $thesis = Thesis::where('comment_id', $thesisToDelete['comment_id'])->first();
        // $comment = Comment::where('id', $thesis->comment_id)->first('id');

        if ($thesis) {
            $week = Week::latest('id')->first();

            if (!$this->checkDateBelongsToCurrentWeek($week->main_timer)) {
                // return $this->jsonResponseWithoutMessage('Cannot delete thesis', 'data', 500);
                throw new \Exception('Cannot delete thesis');
            }

            $week_id = $week->id;

            $mark_record = Mark::where('id', $thesis->mark_id)
                ->where('user_id', Auth::id())
                ->where('week_id', $week_id)
                ->first();

            if ($mark_record) {
                $thesis->delete();
                // $comment->delete();

                $thesis_mark = $this->calculate_mark_for_all_thesis($thesis->mark_id);

                $reading_mark = $thesis_mark['reading_mark'];
                $writing_mark = $thesis_mark['writing_mark'];

                if ($reading_mark > config('constants.FULL_READING_MARK')) {
                    $reading_mark = config('constants.FULL_READING_MARK');
                }

                if ($writing_mark > config('constants.FULL_WRITING_MARK')) {
                    $writing_mark = config('constants.FULL_WRITING_MARK');
                }

                $mark_data_to_update = array(
                    'total_pages'      => $mark_record->total_pages - ($thesis->end_page - $thesis->start_page + 1),
                    'total_thesis'     => $mark_record->total_thesis - ($thesis->max_length > 0 ? INCREMENT_VALUE : 0),
                    'total_screenshot' => $mark_record->total_screenshot - $thesis->total_screenshots,
                    'reading_mark' => $reading_mark,
                    'writing_mark' => $writing_mark,
                );

                $mark_record->update($mark_data_to_update);
                $this->createOrUpdateUserBook($thesis, true);

                return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis deleted successfully!');
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotFound;
        }
    }

    /**
     * calculate mark for all week theses
     * @author
     * @todo get all theses in the same week
     * @todo calculate mark for each thesis based on its type
     * @todo calculate total mark for the week
     * @param int $mark_id          
     * @return array ['reading_mark', 'writing_mark']
     */
    public function calculate_mark_for_all_thesis($mark_id)
    {
        $default_mark = [
            'reading_mark' => 0,
            'writing_mark' => 0,
        ];
        $mark = $default_mark;
        $theses = Thesis::where('mark_id', $mark_id)->get();

        foreach ($theses as $thesis) {
            $thesis_mark = $default_mark;
            $totalPages = $thesis->end_page - $thesis->start_page > 0 ? $thesis->end_page - $thesis->start_page + 1 : 0;
            if ($thesis->type->type === NORMAL_THESIS_TYPE) {
                $thesis_mark = $this->calculate_mark_for_normal_thesis(
                    $totalPages,
                    $thesis->max_length,
                    $thesis->total_screenshots,

                );
            } else if ($thesis->type->type === RAMADAN_THESIS_TYPE || $thesis->type->type === TAFSEER_THESIS_TYPE) {
                $thesis_mark = $this->calculate_mark_for_ramadan_thesis(
                    $totalPages,
                    $thesis->max_length,
                    $thesis->total_screenshots,
                    $thesis->type->type,

                );
            }
            $mark['reading_mark'] += $thesis_mark['reading_mark'];

            //modify mark if the thesis is audited
            if ($thesis->status === 'accepted' || $thesis->status === 'pending') {
                $mark['writing_mark'] += $thesis_mark['writing_mark'];
            } else if ($thesis->status === 'one_thesis') {
                $mark['writing_mark'] += config('constants.PART_WRITING_MARK');
            }
        }
        return $mark;
    }

    /**
     * calculate mark for normal thesis
     * @author Asmaa
     * @todo check if the thesis is within a duration of exams exception and if it satisfies the conditions of the exception
     * @todo calculate the number of parts
     * @todo calculate the number of remaining pages out of part
     * @todo calculate the reading mark based on the number of parts and total pages
     * @todo calculate the writing mark based on the number of parts and max length or total screenshots
     * @param int $total_pages
     * @param int $max_length
     * @param int $total_screenshots
     * @return array ['reading_mark', 'writing_mark']     
     */
    public function calculate_mark_for_normal_thesis($total_pages, $max_length, $total_screenshots)
    {
        //if the thesis is within a duration of exams exception, the mark will be full if the user satisfies the conditions
        $is_exams_exception = $this->check_exam_exception();
        if ($is_exams_exception) {
            if ($total_pages >= 10 && ($max_length >= COMPLETE_THESIS_LENGTH || $total_screenshots >= MAX_SCREENSHOTS)) {
                return [
                    'reading_mark' => config('constants.FULL_READING_MARK'),
                    'writing_mark' => config('constants.FULL_WRITING_MARK'),
                ];
            }
        }

        $number_of_parts = (int) ($total_pages / PART_PAGES);
        $number_of_remaining_pages_out_of_part = $total_pages % PART_PAGES; //used if the parts less than 5 

        if ($number_of_parts > MAX_PARTS) { //if the parts exceeded the max number 
            $number_of_parts = MAX_PARTS;
        } else if (
            $number_of_parts < MAX_PARTS &&
            $number_of_remaining_pages_out_of_part >= MIN_VALID_REMAINING
        ) {
            $number_of_parts += INCREMENT_VALUE;
        }
        //reading mark    
        $reading_mark = $number_of_parts * config('constants.PART_READING_MARK');
        $thesis_mark = 0;
        if ($max_length > 0) {

            if ($max_length >= COMPLETE_THESIS_LENGTH) { //COMPLETE THESIS                           
                $thesis_mark = $number_of_parts * config('constants.PART_WRITING_MARK');
            } else { //INCOMPLETE THESIS
                $thesis_mark = config('constants.PART_WRITING_MARK');

                //if screenshots exist
                if ($total_screenshots > 0) {

                    //decresing the number of parts by 1 since the first part is for the incomplete thesis
                    $number_of_parts -= 1;

                    $screenshots = $total_screenshots;
                    if ($screenshots >= MAX_SCREENSHOTS) {
                        $screenshots = MAX_SCREENSHOTS;
                    }
                    if ($screenshots > $number_of_parts) {
                        $screenshots = $number_of_parts;
                    }

                    $thesis_mark += $screenshots * config('constants.PART_WRITING_MARK');
                }
            }
        } else if ($total_screenshots > 0) {
            $screenshots = $total_screenshots;
            if ($screenshots >= MAX_SCREENSHOTS) {
                $screenshots = MAX_SCREENSHOTS;
            }
            if ($screenshots > $number_of_parts) {
                $screenshots = $number_of_parts;
            }

            $thesis_mark += $screenshots * config('constants.PART_WRITING_MARK');
        }

        return [
            'reading_mark' => $reading_mark ?? 0,
            'writing_mark' => $thesis_mark ?? 0,
        ];
    }

    /**
     * calculate mark for ramadan thesis (steps are the same as normal thesis, but the max number of parts is 3)
     * @author Asmaa
     * @param int $total_pages
     * @param int $max_length
     * @param int $total_screenshots
     * @param string $thesis_type
     * @return array ['reading_mark', 'writing_mark']
     */
    public function calculate_mark_for_ramadan_thesis($total_pages, $max_length, $total_screenshots, $thesis_type)
    {
        if ($max_length <= 0 && $total_screenshots <= 0) { //if no thesis -- it is considered as normal thesis
            return $this->calculate_mark_for_normal_thesis($total_pages, $max_length, $total_screenshots);
        }

        //if the thesis is within a duration of exams exception, the mark will be full if the user satisfies the conditions
        $is_exams_exception = $this->check_exam_exception();
        if ($is_exams_exception) {
            if ($total_pages >= 10 && ($max_length >= COMPLETE_THESIS_LENGTH || $total_screenshots >= MAX_SCREENSHOTS)) {
                return [
                    'reading_mark' => config('constants.FULL_READING_MARK'),
                    'writing_mark' => config('constants.FULL_WRITING_MARK'),
                ];
            }
        }

        $number_of_parts = 0;
        if ($thesis_type === RAMADAN_THESIS_TYPE) {
            $number_of_parts = (int) ($total_pages / RAMADAN_PART_PAGES);
        }
        //tafseer thesis consedered based on the number of ayats
        else if ($thesis_type === TAFSEER_THESIS_TYPE) {
            $number_of_parts = $total_pages;
        }

        if ($number_of_parts > MAX_PARTS) { //if the parts exceeded the max number 
            $number_of_parts = MAX_PARTS;
        }

        //reading mark    
        $reading_mark = $number_of_parts * config('constants.PART_READING_MARK');
        $thesis_mark = 0;
        if ($max_length > 0) {
            if ($max_length >= COMPLETE_THESIS_LENGTH) { //COMPLETE THESIS                           
                $thesis_mark = $number_of_parts * config('constants.PART_WRITING_MARK');
            } else { //INCOMPLETE THESIS                
                $thesis_mark += config('constants.PART_WRITING_MARK');

                //if screenshots exist
                if ($total_screenshots > 0) {

                    //decresing the number of parts by 1 since the first part is for the incomplete thesis
                    $number_of_parts -= 1;

                    $screenshots = $total_screenshots;
                    if ($screenshots >= MAX_SCREENSHOTS) {
                        $screenshots = MAX_SCREENSHOTS;
                    }
                    if ($screenshots > $number_of_parts) {
                        $screenshots = $number_of_parts;
                    }

                    $thesis_mark += $screenshots * config('constants.PART_WRITING_MARK');
                }
            }
        } else if ($total_screenshots > 0) {
            $screenshots = $total_screenshots;
            if ($screenshots >= MAX_SCREENSHOTS) {
                $screenshots = MAX_SCREENSHOTS;
            }
            if ($screenshots > $number_of_parts) {
                $screenshots = $number_of_parts;
            }
            $thesis_mark = $screenshots * config('constants.PART_WRITING_MARK');
        }

        return [
            'reading_mark' => $reading_mark ?? 0,
            'writing_mark' => $thesis_mark ?? 0,
        ];
    }

    /**
     * check if the user has an exception for exams in the current date
     * @author Asmaa
     * @return boolean
     */
    public function check_exam_exception()
    {
        $user_exception = UserException::where('user_id', Auth::id())
            ->where('status', config('constants.ACCEPTED_STATUS'))
            ->whereDate('end_at', '>', Carbon::now())
            ->whereDate('start_at', '<=', Carbon::now())
            ->with('type', function ($query) {
                $query->where('type', config('constants.EXAMS_MONTHLY_TYPE'))
                    ->orWhere('type', config('constants.EXAMS_SEASONAL_TYPE'));
            })
            ->latest('id')
            ->first();

        $is_exams_exception = false;
        if ($user_exception) {
            $is_exams_exception = true;
        }

        return $is_exams_exception;
    }

    /**
     * Create a user book record if it doesn't exist, or update it if it exists
     * @param Thesis $thesis
     * @return void
     */
    public function createOrUpdateUserBook($thesis, $isDeleted = false)
    {
        $book_id = $thesis->book_id;
        $user_id = $thesis->user_id;
        $user = User::find($user_id);

        //get the latest user book related to the user and the book
        $user_book = UserBook::where('user_id', $user_id)
            ->where('book_id', $book_id)
            ->latest()
            ->first();

        //if the user book doesn't exist
        if (!$user_book) {
            if (!$isDeleted) {
                //if new thesis is added, create a new user book record
                $book = Book::find($book_id);
                $user_book = UserBook::create([
                    'user_id' => $user_id,
                    'book_id' => $book_id,
                    'status' => $thesis->end_page >= $book->end_page ? 'finished' : 'in progress',
                ]);
            }
        } else {

            //if the thesis is deleted
            if ($isDeleted) {
                //if the user book status is finished, decrease the counter and change the status to in progress
                if ($user_book->status == 'finished') {
                    $user_book->status = 'in progress';
                    $user_book->counter = $user_book->counter - 1;
                    $user_book->save();
                }
            } else {
                //if user book exists, update the status based on the thesis end page
                if ($thesis->end_page >= $user_book->book->end_page) {
                    $user_book->status = 'finished';
                    $user_book->counter = $user_book->counter + 1;
                    $user_book->save();
                }

                //is status "later", uptade it to "in progress" as the user will start reading the book
                else if ($user_book->status == 'later' || $user_book->status == 'finished') {
                    $user_book->status = 'in progress';
                    $user_book->save();
                }
            }
        }

        //fix counter and status (updating and deleting will cause some mistakes in the status and counter)
        $allThesis = $user->theses()->where('book_id', $book_id)->count();
        if ($allThesis <= 0) {
            if ($user_book->status != 'later') {
                $user_book->delete();
            }
        }

        //needs updating
        // else {
        //     $completeTheses = $user->theses()->where('end_page', $user_book->book->end_page)->where('book_id', $book_id)->count();
        //     $user_book->counter = $completeTheses;
        //     $user_book->status = $allThesis > $completeTheses ? 'in progress' : 'finished';
        //     $user_book->save();
        // }
    }

    /**
     * Modify thesis mark based on the status
     * @param int $mark_id
     * @param string $status
     * @return void
     */
    public function auditThesis($thesis, $status)
    { //status: rejected or one_thesis

        if ($status != 'rejected' && $status != 'one_thesis') {
            return;
        }

        $mark = null;
        if ($thesis->type->type === NORMAL_THESIS_TYPE) {
            $mark = $this->calculate_mark_for_normal_thesis(
                $thesis->end_page - $thesis->start_page + 1,
                $thesis->max_length,
                $thesis->total_screenshots
            );
        } else if ($thesis->type->type === RAMADAN_THESIS_TYPE || $thesis->type->type === TAFSEER_THESIS_TYPE) {
            $mark = $this->calculate_mark_for_ramadan_thesis(
                $thesis->end_page - $thesis->start_page + 1,
                $thesis->max_length,
                $thesis->total_screenshots,
                $thesis->type->type
            );
        }

        if (!$mark) {
            return;
        }

        $markRecord = Mark::find($thesis->mark_id);
        if ($status == 'rejected') {
            $markRecord->writing_mark = $markRecord->writing_mark - $mark['writing_mark'];
            $markRecord->save();
        } else if ($status == 'one_thesis') {
            $markRecord->writing_mark = $markRecord->writing_mark - $mark['writing_mark'] + config('constants.PART_WRITING_MARK');
            $markRecord->save();
        }
    }
}