<?php

namespace App\Traits;

use App\Constants\MarkConstants;
use App\Constants\StatusConstants;
use App\Constants\ThesisConstants;
use App\Exceptions\NotFound;
use App\Http\Resources\ThesisResource;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Mark;
use App\Models\ModificationReason;
use App\Models\RamadanDay;
use App\Models\Thesis;
use App\Models\ThesisType;
use App\Models\User;
use App\Models\UserBook;
use App\Models\UserException;
use App\Models\Week;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Error\Deprecated;

trait ThesisTraits
{
    use ResponseJson;

    ##########ASMAA##########

    public function createThesis(array $thesisData)
    {
        $userId = Auth::id();

        $week = $this->getCurrentWeek();

        $this->ensureValidWeek($week->main_timer);

        $userMark = $this->getUserMark($week->id);

        $this->ensureThesisNotDuplicated($thesisData, $userMark->id, $userId);

        $thesisDataToInsert = $this->prepareThesisDataForCreate($thesisData, $userMark->id, $userId, $week->is_vacation);

        $allTheses = $this->getAllUserTheses($userMark->user_id, $userMark->id);
        $markData = $this->calculateMarks($allTheses, $thesisData);

        //check if the thesis is readonly then accept it
        if ($markData['total_pages'] > 0 && $markData['total_thesis'] == 0 && $markData['total_screenshot'] == 0) {
            $thesisDataToInsert['status'] = StatusConstants::ACCEPTED_STATUS;
        }

        // dd("All Theses", $allTheses, "Mark Data", $markData, "Thesis Data", $thesisDataToInsert);

        $thesis = Thesis::create($thesisDataToInsert);

        if ($thesis) {
            $this->createOrUpdateUserBook($thesis);
            $userMark->update($markData);

            $this->checkUserHasException();
            return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'تم إضافة الأطروحة بنجاح');
        }

        throw new \Exception('حدث خطأ أثناء إضافة الأطروحة, يرجى المحاولة مرة أخرى');
    }

    public function updateThesis(Thesis $thesisToUpdate)
    {
        $week = $this->getCurrentWeek();

        $this->ensureValidWeek($week->main_timer);

        $thesis = $this->getThesis($thesisToUpdate['comment_id']);
        $userMark = $this->getUserMark($week->id, $thesis->mark_id);

        $thesisDataToUpdate = $this->prepareThesisDataForUpdate($thesisToUpdate);

        $allTheses = $this->getAllUserTheses($userMark->user_id, $userMark->id, $thesis->id);

        $markData = $this->calculateMarks($allTheses, $thesisToUpdate, false);

        $thesis->update($thesisDataToUpdate);
        $userMark->update($markData);
        $this->createOrUpdateUserBook($thesis);

        return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'تم تعديل الأطروحة بنجاح');
    }

    public function deleteThesis(Thesis $thesisToDelete)
    {
        $thesis = Thesis::where('comment_id', $thesisToDelete['comment_id'])
            ->with('modifiedTheses')
            ->firstOrFail();

        $week = $this->getCurrentWeek();

        $this->ensureValidWeek($week->main_timer);

        $userMark = $this->getUserMark($week->id, $thesis->mark_id);

        $allTheses = $this->getAllUserTheses($userMark->user_id, $userMark->id, $thesis->id);

        $markData = $this->calculateMarks($allTheses, null, false);

        //delete modified thesis if exists
        $modifiedThesis = $thesis->modifiedTheses;
        if ($modifiedThesis) {
            $modifiedThesis->delete();
        }

        $thesis->delete();
        $userMark->update($markData);
        $this->createOrUpdateUserBook($thesis, true);

        return $this->jsonResponse(new ThesisResource($thesis), 'data', 200, 'Thesis deleted successfully!');
    }

    public function calculateAllThesesMark(int $mark_id, bool $update = false): array
    {
        //new approach
        $userMark = $this->getUserMark(null, $mark_id);
        $allTheses = $this->getAllUserTheses($userMark->user_id, $userMark->id);

        $markData = $this->calculateMarks($allTheses, null, false);

        if ($update) {
            $userMark->update($markData);
        }

        return $markData;
    }

    public function createOrUpdateUserBook(Thesis $thesis, bool $isDeleted = false): void
    {
        $book = $thesis->book;
        $user = User::find($thesis->user_id);

        $user_book = UserBook::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->latest()
            ->first();

        $percentage = $this->calculateBookPagesPercentage($user, $book);

        if (!$user_book && !$isDeleted) {
            // Create new UserBook if not found and not deleting
            $finished = $thesis->end_page >= $book->end_page && $percentage >= 85;
            UserBook::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'status' => $finished ? 'finished' : 'in progress',
                'counter' => $finished ? 1 : 0,
                'finished_at' => $finished ? now() : null,
            ]);
        } elseif ($user_book) {
            if ($isDeleted) {
                //TODO check percentage before changing the status and the counter
                // Handle deletion scenario
                if ($user_book->status == 'finished') {
                    $user_book->status = 'in progress';
                    $user_book->counter = max($user_book->counter - 1, 0);
                    $user_book->finished_at = $user_book->counter <= 0 ? null : $user_book->finished_at;
                    $user_book->save();
                }
            } else {
                // Update existing UserBook
                if ($user_book->status == 'in progress') {
                    $lastPageThesis = $user->theses()
                        ->where('end_page', $book->end_page)
                        ->where('book_id', $book->id);

                    $lastPageThesis = $user_book->finished_at ?
                        $lastPageThesis->where('updated_at', '>', $user_book->finished_at) :
                        $lastPageThesis->where('updated_at', '>=', $user_book->updated_at);

                    $lastPageThesis = $lastPageThesis->latest()->first();

                    if ($percentage >= 85 && $lastPageThesis) {
                        $user_book->status = 'finished';
                        $user_book->counter++;
                        $user_book->finished_at = now();
                        $user_book->save();
                    }
                } elseif (in_array($user_book->status, ['later', 'finished'])) {
                    $finished = $thesis->end_page >= $book->end_page && $percentage >= 85;
                    $user_book->status = $finished ? 'finished' : 'in progress';
                    if ($finished) {
                        $user_book->counter++;
                        $user_book->finished_at = now();
                    }
                    $user_book->save();
                }
            }
        }
    }

    public function checkExamException(): bool
    {
        $userId = Auth::id();

        return  Cache::remember("user_exam_exception_$userId", 600, function () use ($userId) {
            return UserException::where('user_id', $userId)
                ->where('status', StatusConstants::ACCEPTED_STATUS)
                ->whereHas('type', function ($query) {
                    $query->whereIn('type', [
                        StatusConstants::EXAMS_MONTHLY_TYPE,
                        StatusConstants::EXAMS_SEASONAL_TYPE
                    ]);
                })
                ->exists();
        });
    }

    private function checkUserHasException(): void
    {
        //check if the user has an exception and cancel the exception if the thesis is added
        $userException = UserException::where('user_id', Auth::id())
            ->whereIn('status', [StatusConstants::ACCEPTED_STATUS, StatusConstants::PENDING_STATUS])
            ->whereHas('type', function ($query) {
                $query->whereIn('type', [
                    StatusConstants::FREEZE_THIS_WEEK_TYPE,
                    StatusConstants::FREEZE_NEXT_WEEK_TYPE,
                    StatusConstants::EXCEPTIONAL_FREEZING_TYPE
                ]);
            })
            ->latest('id')
            ->first();

        if ($userException) {
            $userException->status = StatusConstants::CANCELED_STATUS;
            $userException->save();
        }
    }

    /**
     * Calculates the percentage of a book's pages that a user has read.
     *
     * @param  \App\Models\User|null  $user
     * @param  \App\Models\Book  $book
     * @param  \Illuminate\Support\Collection|null  $theses
     * @return float
     */
    public function calculateBookPagesPercentage(?User $user, Book $book, ?Collection $theses = null): float
    {
        $theses = $theses ?? $user->theses()->where('book_id', $book->id)->get();

        // Use a collection sum method for efficiency
        $totalPages = $theses->sum(function ($thesis) {
            return $thesis->end_page - $thesis->start_page + 1;
        });

        $bookTotalPages = $book->end_page - $book->start_page;
        $totalPages = $totalPages > $bookTotalPages ? $bookTotalPages : $totalPages;

        // Ensure we avoid division by zero
        return $bookTotalPages > 0 ? round(($totalPages / $bookTotalPages) * 100) : 0;
    }

    /**
     * Checks if the given page range overlaps with previous theses
     * added by the user in the last month.
     *
     * @param int $start_page
     * @param int $end_page
     * @param int $book_id
     * @param int $user_id
     * @param int|null $thesis_id
     * @return bool
     */
    public function checkOverlap($start_page, $end_page, $book_id, $user_id, $thesis_id = null)
    {
        $overlapThreshold = 0.5;
        $overlapDate = Carbon::now()->subMonth();

        //get user book
        $userBook = UserBook::where('user_id', $user_id)
            ->where('book_id', $book_id)
            ->first();

        if (!$userBook) {
            return false;
        }

        //calculate the range of selected pages
        $selectedRange = range($start_page, $end_page);

        //calculate the minimum required overlap count
        $requiredOverlapCount = ceil(count($selectedRange) * $overlapThreshold);

        $overlappedTheses = Thesis::where('book_id', $book_id)
            ->where('user_id', $user_id)
            ->where(function ($query) use ($start_page, $end_page) {
                $query->whereBetween('start_page', [$start_page, $end_page])
                    ->orWhereBetween('end_page', [$start_page, $end_page]);
            })
            ->where('created_at', '>=', $overlapDate);

        //get the theses that were added after the user book was finished
        if ($userBook->finished_at) {
            $overlappedTheses->where('created_at', '>', $userBook->finished_at);
        }
        //TODO: Remove this else block after releasing the new version
        else if ($userBook->counter > 0) {
            $overlappedTheses->where('created_at', '>', $userBook->updated_at);
        }


        //if the thesis id is provided, exclude it from the overlap check (edit case)
        if ($thesis_id) {
            $overlappedTheses->where('id', '!=', $thesis_id);
        }

        //reduce method
        $overlapCount = $overlappedTheses
            ->get()
            ->reduce(function ($carry, $thesis) use ($selectedRange) {
                //calculate overlap between selected pages and previous thesis pages
                $thesisRange = range($thesis->start_page, $thesis->end_page);
                $overlap = array_intersect($selectedRange, $thesisRange);
                return $carry + count($overlap);
            }, 0);

        return $overlapCount >= $requiredOverlapCount;
    }

    private function prepareThesisDataForCreate(array $thesisData, int $markId, int $userId, bool $isVacation): array
    {
        $maxLength = array_key_exists('max_length', $thesisData) ? $thesisData['max_length'] : 0;
        $totalScreenshots = array_key_exists('total_screenshots', $thesisData) ? $thesisData['total_screenshots'] : 0;

        return [
            'comment_id' => $thesisData['comment_id'],
            'book_id' => $thesisData['book_id'],
            'mark_id' => $markId,
            'user_id' => $userId,
            'type_id' => $thesisData['type_id'],
            'start_page' => $thesisData['start_page'],
            'end_page' => $thesisData['end_page'],
            'max_length' => $maxLength,
            'total_screenshots' => $totalScreenshots,
            'status' => $isVacation ? StatusConstants::ACCEPTED_STATUS : StatusConstants::PENDING_STATUS,
        ];
    }

    private function prepareThesisDataForUpdate($thesisToUpdate): array
    {
        $totalPages = max(0, $thesisToUpdate['end_page'] - $thesisToUpdate['start_page'] + 1);
        $maxLength = $thesisToUpdate['max_length'] ?? 0;
        $totalScreenshots = $thesisToUpdate['total_screenshots'] ?? 0;

        return [
            'total_pages'       => $totalPages,
            'max_length'        => $maxLength,
            'total_screenshots' => $totalScreenshots,
            'start_page'        => $thesisToUpdate['start_page'],
            'end_page'          => $thesisToUpdate['end_page'],
            'status'            => 'pending',
        ];
    }

    private function calculateMarks(Collection $allTheses, array|Thesis|null $thesis, bool $resetFreeze = true): array
    {
        $isRamadanActive = $this->checkRamadanStatus();

        $normalThesesMark = $ramadanThesesMark = null;
        $thesisType = $thesis ? ThesisType::findOrFail($thesis['type_id'])->type : null;

        //convert thesis to array
        $thesisArray = $thesis ? (is_array($thesis) ? $thesis : $thesis->toArray()) : null;

        $finalTotals = [
            'total_pages' => 0,
            'total_theses' => 0,
            'total_screenshots' => 0,
            'total_freezes' => 0,
            'total_rejected_parts' => 0,
        ];

        //if allTheses is empty, calculate the mark for the provided thesis only
        // if ($allTheses->isEmpty() && $thesis) {
        //     return $this->calculateSingleThesisMarks($thesisArray, $thesisType, $isRamadanActive, $resetFreeze);
        // }

        //add the current thesis to the allTheses collection based on its type
        if ($thesisArray && $thesisType) {
            if ($allTheses->has($thesisType)) {
                $allTheses[$thesisType]->push(new Thesis($thesisArray));
            } else {
                $allTheses[$thesisType] = collect([new Thesis($thesisArray)]);
            }
        }

        foreach ($allTheses as $type => $theses) {
            list($actualTotals, $filteredTotals) = $this->aggregateTheses($theses);

            $finalTotals['total_pages'] += $filteredTotals['total_pages'];
            $finalTotals['total_theses'] += $filteredTotals['total_theses'];
            $finalTotals['total_screenshots'] += $filteredTotals['total_screenshots'];
            $finalTotals['total_rejected_parts'] += $filteredTotals['total_rejected_parts'];

            if (strcasecmp($type, ThesisConstants::NORMAL_THESIS_TYPE) === 0) {
                $normalThesesMark = $this->calculateNormalThesesMark($filteredTotals);
            } elseif (strcasecmp($type, ThesisConstants::RAMADAN_THESIS_TYPE) === 0 || strcasecmp($type, ThesisConstants::TAFSEER_THESIS_TYPE) === 0) {
                $ramadanThesesMark = $isRamadanActive
                    ? $this->calculateRamadanThesesMark($filteredTotals, $type)
                    : $this->calculateNormalThesesMark($filteredTotals);
            }
        }

        return $this->mergeMarks($normalThesesMark, $ramadanThesesMark, $finalTotals, $resetFreeze);
    }

    private function calculateSingleThesisMarks(array $thesisArray, ?string $thesisType, bool $isRamadanActive, bool $resetFreeze): array
    {
        [$actualTotals, $filteredTotals] = $this->getThesisTotals($thesisArray);

        $normalThesesMark = $ramadanThesesMark = null;

        if (strcasecmp($thesisType, ThesisConstants::NORMAL_THESIS_TYPE) === 0) {
            $normalThesesMark = $this->calculateNormalThesesMark($filteredTotals);
        } elseif (in_array($thesisType, [ThesisConstants::RAMADAN_THESIS_TYPE, ThesisConstants::TAFSEER_THESIS_TYPE])) {
            $ramadanThesesMark = $isRamadanActive
                ? $this->calculateRamadanThesesMark($filteredTotals, $thesisType)
                : $this->calculateNormalThesesMark($filteredTotals);
        }

        return $this->mergeMarks($normalThesesMark, $ramadanThesesMark, $filteredTotals, $resetFreeze);
    }

    private function aggregateSingleThesis(array $thesisArray, array &$actualTotals, array &$filteredTotals): void
    {
        [$actual, $filtered] = $this->getThesisTotals($thesisArray);

        //add the actual values to the total values
        $actualTotals['total_pages'] += $actual['total_pages'];
        $actualTotals['total_theses'] += $actual['total_theses'];
        $actualTotals['total_screenshots'] += $actual['total_screenshots'];
        $actualTotals['total_rejected_parts'] += $actual['total_rejected_parts'];

        //add the filtered values to the total values
        $filteredTotals['total_pages'] += $filtered['total_pages'];
        $filteredTotals['total_theses'] += $filtered['total_theses'];
        $filteredTotals['total_screenshots'] += $filtered['total_screenshots'];
        $filteredTotals['complete_theses_count'] += $filtered['complete_theses_count'];
        $filteredTotals['total_rejected_parts'] += $filtered['total_rejected_parts'];
    }


    private function aggregateTheses(Collection | SupportCollection | Thesis $theses): array
    {
        $actualTotals = [
            'total_pages' => 0,
            'total_theses' => 0,
            'total_screenshots' => 0,
            'total_rejected_parts' => 0,
        ];

        $filteredTotals = [
            'total_pages' => 0,
            'total_theses' => 0,
            'total_screenshots' => 0,
            'complete_theses_count' => 0,
            'total_rejected_parts' => 0,
        ];

        foreach ($theses as $thesis) {
            $this->aggregateSingleThesis($thesis->toArray(), $actualTotals, $filteredTotals);
        }

        return [$actualTotals, $filteredTotals];
    }

    private function mergeMarks(?array $normalMark, ?array $ramadanMark, array $totals, bool $resetFreeze = true): array
    {
        $readingMark = ($normalMark ? $normalMark['reading_mark'] : 0) + ($ramadanMark ? $ramadanMark['reading_mark'] : 0);
        $writingMark = ($normalMark ? $normalMark['writing_mark'] : 0) + ($ramadanMark ? $ramadanMark['writing_mark'] : 0);

        $readingMark = min($readingMark, MarkConstants::FULL_READING_MARK);
        $writingMark = max(0, $writingMark - ($totals['total_rejected_parts'] * MarkConstants::PART_WRITING_MARK));
        $writingMark = min($writingMark, MarkConstants::FULL_WRITING_MARK);

        $data = [
            'total_pages' => $totals['total_pages'],
            'total_thesis' => $totals['total_theses'],
            'total_screenshot' => $totals['total_screenshots'],
            'reading_mark' => $readingMark,
            'writing_mark' => $writingMark,
        ];

        if ($resetFreeze) {
            $data['is_freezed'] = 0;
        }

        return $data;
    }

    private function calculateExamThesisMark(int $totalPages, int $totalScreenshots, int $totalTheses, int $completeThesesCount): ?array
    {
        // Check if the thesis meets the criteria for full marks
        $meetsCriteria = $totalPages >= 10 &&
            ($completeThesesCount > 0 || $totalTheses >= 2 || $totalScreenshots >= 2 || ($totalTheses + $totalScreenshots) >= 2);

        // Return the mark if criteria are met, otherwise return null
        return $meetsCriteria ? [
            'reading_mark' => MarkConstants::FULL_READING_MARK,
            'writing_mark' => MarkConstants::FULL_WRITING_MARK,
        ] : null;
    }

    private function calculateNormalThesesMark(array $filteredTotals): array
    {
        $totalPages = $filteredTotals['total_pages'];
        $totalTheses = $filteredTotals['total_theses'];
        $totalScreenshots = $filteredTotals['total_screenshots'];
        $completeThesesCount = $filteredTotals['complete_theses_count'];

        // Check if there is an exam exception and calculate mark accordingly
        if ($this->checkExamException()) {
            $examMark = $this->calculateExamThesisMark($totalPages, $totalScreenshots, $totalTheses, $completeThesesCount);
            if ($examMark) {
                return $examMark;
            }
        }

        // Calculate the number of parts and the remaining pages
        $parts = (int) ($totalPages / MarkConstants::PART_PAGES);
        $remainingPagesOutOfPart = $totalPages % MarkConstants::PART_PAGES;

        // Adjust the number of parts based on the remaining pages
        if ($parts < MarkConstants::MAX_PARTS && $remainingPagesOutOfPart >= MarkConstants::MIN_VALID_REMAINING) {
            $parts += MarkConstants::INCREMENT_THESIS_VALUE;
        }
        $parts = min($parts, MarkConstants::MAX_PARTS);

        // Calculate the reading mark
        $readingMark = $parts * MarkConstants::PART_READING_MARK;

        // Initialize thesis mark
        $thesisMark = 0;

        // Calculate the thesis mark based on the number of theses and their status
        if ($totalTheses > 0) {
            $thesisMark = $completeThesesCount > 0
                ? $parts * MarkConstants::PART_WRITING_MARK
                : $totalTheses * MarkConstants::PART_WRITING_MARK;

            // Add marks for screenshots if applicable
            if ($thesisMark < MarkConstants::FULL_WRITING_MARK && $totalScreenshots > 0 && $completeThesesCount === 0) {
                // Adjust parts if they have already been used for thesis mark calculation
                $parts -= min($totalTheses, $parts);
                if ($parts > 0) {
                    $screenshots = $this->getMaxTotalScreenshots($totalScreenshots, $parts);
                    $thesisMark += $screenshots * MarkConstants::PART_WRITING_MARK;
                }
            }
        } elseif ($totalScreenshots > 0) {
            $screenshots = $this->getMaxTotalScreenshots($totalScreenshots, $parts);
            $thesisMark += $screenshots * MarkConstants::PART_WRITING_MARK;
        }

        return [
            'reading_mark' => $readingMark,
            'writing_mark' => $thesisMark,
        ];
    }

    private function calculateRamadanThesesMark(array $filteredTotals, string $type): array
    {
        $totalPages = $filteredTotals['total_pages'];
        $totalTheses = $filteredTotals['total_theses'];
        $totalScreenshots = $filteredTotals['total_screenshots'];
        $completeThesesCount = $filteredTotals['complete_theses_count'];

        // Validate Ramadan thesis conditions
        if (strcasecmp($type, ThesisConstants::RAMADAN_THESIS_TYPE) === 0) {
            if ($totalPages < 10 || ($totalTheses > 0 && $completeThesesCount === 0)) {
                throw new \Exception('أطروحة رمضان يجب أن تكون أكثر من 10 صفحات وشاملة');
            }
            return $this->getRamadanMark($totalPages, $totalScreenshots, $completeThesesCount);
        }

        // Validate Tafseer thesis conditions
        if (strcasecmp($type, ThesisConstants::TAFSEER_THESIS_TYPE) === 0) {
            if ($totalPages < 2 || ($completeThesesCount === 0 && $totalScreenshots < 1)) {
                throw new \Exception('أطروحة التفسير يجب أن تكون أكثر من صفحتين وشاملة أو تحتوي اقتباس واحد على الأقل');
            }
            return $this->getTafseerMark($totalPages);
        }
    }

    private function getRamadanMark(int $totalPages, int $totalScreenshots, int $completeThesesCount): array
    {
        /* Rules
            الورد الأسبوعي للحصول على العلامة الكاملة هو (15) صفحة و أطروحة واحدة من 400 حرف او رفع عدد 1 اقتباس
            يحصل السفير على علامة 80 من مائة في حال قرأ (10) صفحات وكتب اطروحة واحدة من 400 حرف او 1 اقتباسات
            يحصل السفير على علامة 70 من مائة في حال قرأ 15 صفحة ولم يكتب اطروحة او اقتباس
            يحصل السفير على علامة 60 من مائة في حال قرأ 10 صفحات ولم يكتب أطروحة او اقتباس .
            لا يسمح بالتصويت اقل من 10 ،
            اي تصويت أقل من 15 يعتبر 10 .
        */

        // Define constants for marks based on the description
        $fullReadingMark = MarkConstants::FULL_READING_MARK;
        $fullWritingMark = MarkConstants::FULL_WRITING_MARK;
        $writingMarkThreshold = 15; // The threshold for writing mark consideration

        // Determine reading and writing marks based on conditions
        if ($completeThesesCount > 0 || $totalScreenshots > 0) {
            // Case where the user has either completed theses or screenshots
            if ($totalPages >= 15) {
                return [
                    'reading_mark' => $fullReadingMark,
                    'writing_mark' => $fullWritingMark,
                ];
            }

            return [
                'reading_mark' => $fullReadingMark,
                'writing_mark' => 80 - $fullReadingMark,
            ];
        }

        // Case where the user has no completed theses and no screenshots
        if ($totalPages >= 15) {
            return [
                'reading_mark' => $fullReadingMark,
                'writing_mark' => 70 - $fullReadingMark,
            ];
        }

        return [
            'reading_mark' => $fullReadingMark,
            'writing_mark' => 60 - $fullReadingMark,
        ];
    }

    private function getTafseerMark(int $totalPages): array
    {
        /*Rules
             صفحات 6 + 400 حرف اطروحة او اقتباس(سكرين شوت >> 100
             صفحات 4 + 400 حرف اطروحة او اقتباس(سكرين شوت) >> 80
             صفحات 2 + 400 حرف أطروحة أو اقتباس (سكرين شوت)>> 70
            اي ادخال غير ذلك، راح يرفض الإدخال ويطلع اله تنبيه انه الورد المسموح به هو المذكور اعلاه .
        */

        // Define constants for marks based on the provided rules
        $fullReadingMark = MarkConstants::FULL_READING_MARK;
        $fullWritingMark = MarkConstants::FULL_WRITING_MARK;

        // Define thresholds and corresponding writing marks
        $thresholds = [
            6 => $fullWritingMark,
            4 => 80 - $fullReadingMark,
            2 => 70 - $fullReadingMark
        ];

        // Determine the writing mark based on the total pages
        foreach ($thresholds as $pages => $writingMark) {
            if ($totalPages >= $pages) {
                return [
                    'reading_mark' => $fullReadingMark,
                    'writing_mark' => $writingMark
                ];
            }
        }

        return [
            'reading_mark' => 0,
            'writing_mark' => 0
        ];
    }

    private function getAllUserTheses(int $userId, int $markId, ?int $excludeThesisId = null)
    {
        return Thesis::where('user_id', $userId)
            ->where('mark_id', $markId)
            ->where('status', '!=', 'rejected')
            ->where('id', '!=', $excludeThesisId)
            ->get()
            ->groupBy('type.type');
    }

    ################ helpers ################
    private function getCurrentWeek(): Week
    {
        return Week::latest('id')->first();
    }

    private function ensureValidWeek($mainTimer)
    {
        if (!$this->isValidDate($mainTimer)) {
            throw new \Exception('لا يمكنك إضافة/تعديل الأطروحة إلا في الأسبوع المتاح');
        }
    }

    private function isValidDate($date): bool
    {
        //check if now is less than the main timer of the week
        return Carbon::now()->lessThan($date) ? true : false;
    }

    protected function checkRamadanStatus(): bool
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;
        return Cache::remember("ramadan_active_$currentYear-$currentMonth", now()->endOfWeek()->addSecond(), function () use ($currentYear) {
            return RamadanDay::whereYear('created_at', $currentYear)
                ->where('is_active', 1)
                ->exists();
        });
    }

    private function getThesis(int $commentId): Thesis
    {
        return Thesis::where('comment_id', $commentId)->firstOrFail();
    }

    private function getThesisTotals(array $thesis): array
    {
        $actualTotals = [
            'total_pages' => 0,
            'total_theses' => 0,
            'total_screenshots' => 0,
            // 'total_rejected_parts_from_complete' => 0, //this will be filled in one case: when there are remaining rejected parts which are less than the max parts and the theses are complete
            'total_rejected_parts' => 0,
        ];

        $filteredTotals = [
            'total_pages' => 0,
            'total_theses' => 0,
            'total_screenshots' => 0,
            'complete_theses_count' => 0,
            'total_rejected_parts' => 0,
        ];

        $actualTotals['total_pages'] = $this->getTotalThesisPages($thesis['start_page'], $thesis['end_page']);
        $actualTotals['total_theses'] = array_key_exists('max_length', $thesis) ? ($thesis['max_length'] > 0 ? MarkConstants::INCREMENT_THESIS_VALUE : 0) : 0;
        $actualTotals['total_screenshots'] = array_key_exists('total_screenshots', $thesis) ? $thesis['total_screenshots'] : 0;
        $actualTotals['total_rejected_parts'] = array_key_exists('rejected_parts', $thesis) ? $thesis['rejected_parts'] ?? 0 : 0;

        if (!array_key_exists('status', $thesis)) {
            $filteredTotals['total_pages'] = $actualTotals['total_pages'];
            $filteredTotals['total_theses'] = $actualTotals['total_theses'];
            $filteredTotals['total_screenshots'] = $actualTotals['total_screenshots'];

            if ($filteredTotals['total_theses'] > 0 && $thesis['max_length'] >= MarkConstants::COMPLETE_THESIS_LENGTH) {
                $filteredTotals['complete_theses_count']++;
            }

            return [$actualTotals, $filteredTotals];
        }

        if (strcasecmp($thesis['status'], StatusConstants::REJECTED_STATUS) === 0) {
            return [$actualTotals, $filteredTotals];
        }

        $filteredTotals['total_pages'] = $actualTotals['total_pages'];

        // ! Deprecated status
        if (strcasecmp($thesis['status'], StatusConstants::REJECTED_WRITING_STATUS) === 0) {
            return [$actualTotals, $filteredTotals];
        }

        if ($actualTotals['total_theses'] > 0 || $actualTotals['total_screenshots'] > 0) {
            // ! Deprecated status
            if (strcasecmp($thesis['status'], StatusConstants::ACCEPTED_ONE_THESIS_STATUS) === 0) {
                if ($actualTotals['total_theses'] > 0 && $actualTotals['total_screenshots'] > 0) {
                    $filteredTotals['total_theses'] = 1;
                } elseif ($actualTotals['total_theses'] > 0) {
                    $filteredTotals['total_theses'] = 1;
                } else {
                    $filteredTotals['total_screenshots'] = 1;
                }
            } else {

                if (strcasecmp($thesis['status'], StatusConstants::REJECTED_PARTS_STATUS) === 0) {
                    $maxRejectedParts = $this->getMaxAllowedThesisWritingParts($filteredTotals['total_pages'], $thesis['max_length'], $actualTotals['total_screenshots']);
                    $maxRejectedParts = min($maxRejectedParts, $actualTotals['total_rejected_parts']);

                    $filteredTotals['total_rejected_parts'] = $maxRejectedParts;
                }

                $filteredTotals['total_theses'] = $actualTotals['total_theses'];
                $filteredTotals['total_screenshots'] = $actualTotals['total_screenshots'];

                if ($filteredTotals['total_theses'] > 0 && $thesis['max_length'] >= MarkConstants::COMPLETE_THESIS_LENGTH) {
                    $filteredTotals['complete_theses_count'] = MarkConstants::INCREMENT_THESIS_VALUE;
                }
            }
        }

        return [$actualTotals, $filteredTotals];
    }

    private function getUserMark(?int $weekId, ?int $markId = null): Mark
    {
        if ($markId) {
            return Mark::findOrFail($markId);
        }

        return Mark::firstOrCreate([
            'user_id' => Auth::id(),
            'week_id' => $weekId,
        ]);
    }

    private function ensureThesisNotDuplicated(array $thesis, int $markId, int $userId): void
    {
        // Query to check if a thesis with the same book, start page, and end page exists for the given mark_id
        $existingThesisQuery = Thesis::where('book_id', $thesis['book_id'])
            ->where('start_page', $thesis['start_page'])
            ->where('end_page', $thesis['end_page'])
            ->where('mark_id', $markId);

        // Check if there are existing theses with the same details
        if ($existingThesisQuery->exists()) {
            $userBook = UserBook::where('user_id', $userId)
                ->where('book_id', $thesis['book_id'])
                ->first();

            // If user has a record of the book
            if ($userBook) {
                // If the book was finished before, consider the last finished time
                if ($userBook->finished_at) {
                    $existingThesisQuery->where('created_at', '>', $userBook->finished_at);
                }
                // Handle the case where the book was read but not finished
                else if ($userBook->counter > 0) {
                    $existingThesisQuery->where('created_at', '>', $userBook->updated_at);
                }
            }

            // If there are still existing theses with the same details, throw an exception
            if ($existingThesisQuery->exists()) {
                throw new \Exception('تم إضافة الأطروحة من قبل بنفس الصفحات');
            }
        }
    }

    public function getTotalThesisPages(int $startPage, int $endPage): int
    {
        return max(0, $endPage - $startPage + 1);
    }

    private function getMaxTotalScreenshots(int $totalScreenshots, int $numberOfParts): int
    {
        $maxScreenshots = min(MarkConstants::MAX_SCREENSHOTS, $numberOfParts);
        return min($totalScreenshots, $maxScreenshots);
    }

    /**
     * Calculates the maximum allowed writing parts for a thesis based on the total pages, max length, and total screenshots.
     * @param int $totalPages
     * @param int $maxLength
     * @param int $totalScreenshots
     * @return int
     */
    public function getMaxAllowedThesisWritingParts(int $totalPages, int $maxLength, int $totalScreenshots): int
    {
        $readingParts = (int) ($totalPages / MarkConstants::PART_PAGES);
        $remainingPagesOutOfPart = $totalPages % MarkConstants::PART_PAGES;

        if ($readingParts < MarkConstants::MAX_PARTS && $remainingPagesOutOfPart >= MarkConstants::MIN_VALID_REMAINING) {
            $readingParts += MarkConstants::INCREMENT_THESIS_VALUE;
        }
        $readingParts = min($readingParts, MarkConstants::MAX_PARTS);

        $isFullThesis = $maxLength >= MarkConstants::COMPLETE_THESIS_LENGTH;
        $writingParts = $isFullThesis ? $readingParts : MarkConstants::INCREMENT_THESIS_VALUE;

        if ($totalScreenshots > 0 && $writingParts < $readingParts) {
            //get the maximum number of screenshots based on the remaining parts of reading
            $screenshots = $this->getMaxTotalScreenshots($totalScreenshots, ($readingParts - $writingParts));
            $writingParts = $writingParts + $screenshots;
        }

        $writingParts = min($writingParts, $readingParts);

        return $writingParts;
    }

    public function getThesisTypeIdFromBook(Book $book): int
    {
        $type = $book->type->type === 'free' ? 'normal' : $book->type->type;

        if ($type === 'ramadan' && !$this->checkRamadanStatus()) {
            $type = 'normal';
        }

        return ThesisType::where('type', $type)->firstOrFail()->id;
    }
}
