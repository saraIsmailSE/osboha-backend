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
        
        if($book->level == 'بسيط'){
            $book_stat->simple += 1;
        }else if($book->level == 'متوسط'){
            $book_stat->intermediate += 1;
        }else{
            $book_stat->advanced += 1;
        }

        if($book->section_id == '1'){
            $book_stat->method_books += 1;
        }else if($book->section_id == '2'){
            $book_stat->ramadan_books += 1;
        }else if($book->section_id == '3'){
            $book_stat->children_books += 1;
        }else{
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

        if($book->level == 'بسيط'){
            $book_stat->simple += 1;
        }else if($book->level == 'متوسط'){
            $book_stat->intermediate += 1;
        }else{
            $book_stat->advanced += 1;
        }

        if($book->section_id == '1'){
            $book_stat->method_books += 1;
        }else if($book->section_id == '2'){
            $book_stat->ramadan_books += 1;
        }else if($book->section_id == '3'){
            $book_stat->children_books += 1;
        }else{
            $book_stat->young_people_books += 1;
        }

        if($old_book['level'] == 'بسيط'){
            $book_stat->simple -= 1;
        }else if($old_book['level'] == 'متوسط'){
            $book_stat->intermediate -= 1;
        }else{
            $book_stat->advanced -= 1;
        }

        if($old_book['section_id'] == '1'){
            $book_stat->method_books -= 1;
        }else if($old_book['section_id'] == '2'){
            $book_stat->ramadan_books -= 1;
        }else if($old_book['section_id'] == '3'){
            $book_stat->children_books -= 1;
        }else{
            $book_stat->young_people_books -= 1;
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
        
        $book_stat->total -=  1;
        
        if($book->level == 'بسيط'){
            $book_stat->simple -= 1;
        }else if($book->level == 'متوسط'){
            $book_stat->intermediate -= 1;
        }else{
            $book_stat->advanced -= 1;
        }

        if($book->section_id == '1'){
            $book_stat->method_books -= 1;
        }else if($book->section_id == '2'){
            $book_stat->ramadan_books -= 1;
        }else if($book->section_id == '3'){
            $book_stat->children_books -= 1;
        }else{
            $book_stat->young_people_books -= 1;
        }

        $book_stat->save();
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
