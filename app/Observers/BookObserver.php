<?php

namespace App\Observers;

use App\Models\Book;
use App\Models\BookStatistics;

class BookObserver
{
    /**
     * Handle the Book "created" event.
     *
     * @param  \App\Models\Book  $book
     * @return void
     */
    public function created(Book $book)
    {
        $book_stat = BookStatistics::latest()->first();

        $book_stat->total +=  1;
        $bookLevel = $book->level->level;

        if ($bookLevel  == 'simple') {
            $book_stat->simple += 1;
        } else if ($bookLevel == 'intermediate') {
            $book_stat->intermediate += 1;
        } else if ($bookLevel == 'advanced') {
            $book_stat->advanced += 1;
        }

        $bookType = $book->type->type;

        if ($bookType == 'normal') {
            $book_stat->method_books += 1;
        } else if ($bookType == 'ramadan' || $bookType == 'tafseer') {
            $book_stat->ramadan_books += 1;
        } else if ($bookType == 'kids') {
            $book_stat->children_books += 1;
        } else if ($bookType == 'young') {
            $book_stat->young_people_books += 1;
        }

        $book_stat->save();
    }

    /**
     * Handle the Book "updated" event.
     *
     * @param  \App\Models\Book  $book
     * @return void
     */
    public function updated(Book $book)
    {
        $old_book = $book->getOriginal();
        $book_stat = BookStatistics::latest()->first();

        $bookLevel = $book->level->level;

        if ($bookLevel == 'simple') {
            $book_stat->simple += 1;
        } else if ($bookLevel == 'intermediate') {
            $book_stat->intermediate += 1;
        } else if ($bookLevel == 'advanced') {
            $book_stat->advanced += 1;
        }

        $bookType = $book->type->type;

        if ($bookType == 'normal') {
            $book_stat->method_books += 1;
        } else if ($bookType == 'ramadan' || $bookType == 'tafseer') {
            $book_stat->ramadan_books += 1;
        } else if ($bookType == 'kids') {
            $book_stat->children_books += 1;
        } else if ($bookType == 'young') {
            $book_stat->young_people_books += 1;
        }

        if ($old_book['level'] == 'simple') {
            if ($book_stat->simple != 0) {
                $book_stat->simple -= 1;
            }
        } else if ($old_book['level'] == 'intermediate') {
            if ($book_stat->intermediate != 0) {
                $book_stat->intermediate -= 1;
            }
        } else if ($old_book['level'] == 'advanced') {
            if ($book_stat->advanced != 0) {
                $book_stat->advanced -= 1;
            }
        }

        $oldBookType = $old_book->type->type;
        if ($oldBookType  == 'normal') {
            if ($book_stat->method_books != 0) {
                $book_stat->method_books -= 1;
            }
        } else if ($oldBookType  == 'ramadan' || $oldBookType  == 'tafseer') {
            if ($book_stat->ramadan_books != 0) {
                $book_stat->ramadan_books -= 1;
            }
        } else if ($oldBookType  == 'kids') {
            if ($book_stat->children_books != 0) {
                $book_stat->children_books -= 1;
            }
        } else if ($oldBookType  == 'young') {
            if ($book_stat->young_people_books != 0) {
                $book_stat->young_people_books -= 1;
            }
        }

        $book_stat->save();
    }

    /**
     * Handle the Book "deleted" event.
     *
     * @param  \App\Models\Book  $book
     * @return void
     */
    public function deleted(Book $book)
    {
        $book_stat = BookStatistics::latest()->first();

        if ($book_stat->total != 0) {
            $book_stat->total -=  1;

            $bookLevel = $book->level->level;
            if ($bookLevel == 'simple') {
                if ($book_stat->simple != 0) {
                    $book_stat->simple -= 1;
                }
            } else if ($bookLevel == 'intermediate') {
                if ($book_stat->intermediate != 0) {
                    $book_stat->intermediate -= 1;
                }
            } else if ($bookLevel == 'advanced') {
                if ($book_stat->advanced != 0) {
                    $book_stat->advanced -= 1;
                }
            }

            $bookType = $book->type->type;
            if ($bookType == 'normal') {
                if ($book_stat->method_books != 0) {
                    $book_stat->method_books -= 1;
                }
            } else if ($bookType == 'ramadan' || $bookType == 'tafseer') {
                if ($book_stat->ramadan_books != 0) {
                    $book_stat->ramadan_books -= 1;
                }
            } else if ($bookType == 'kids') {
                if ($book_stat->children_books != 0) {
                    $book_stat->children_books -= 1;
                }
            } else if ($bookType == 'young') {
                if ($book_stat->young_people_books != 0) {
                    $book_stat->young_people_books -= 1;
                }
            }

            $book_stat->save();
        }
    }

    /**
     * Handle the Book "restored" event.
     *
     * @param  \App\Models\Book  $book
     * @return void
     */
    public function restored(Book $book)
    {
        //
    }

    /**
     * Handle the Book "force deleted" event.
     *
     * @param  \App\Models\Book  $book
     * @return void
     */
    public function forceDeleted(Book $book)
    {
        //
    }
}