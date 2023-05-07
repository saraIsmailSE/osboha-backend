<?php

namespace Database\Seeders;

use App\Models\Media;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class booksMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Media::create([
            'media' => 'books/6f689e23-0445-4783-a321-091a9d8d0203.jpg',
            'type'=>'image',
            'user_id'=>1,
            'book_id'=>3,
            'created_at'=>Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'=>Carbon::now()->format('Y-m-d H:i:s'),

        ]);

        Media::create([
            'media' => 'books/c8d4499c-c421-415c-82b0-3bd5d3a612ef.jpg',
            'type'=>'image',
            'user_id'=>1,
            'book_id'=>3,
            'created_at'=>Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'=>Carbon::now()->format('Y-m-d H:i:s'),

        ]);

        
        
        Media::create([
            'media' => 'books/4c90315c-9fe8-43d0-84dd-c35cc73cf97a.jpg',
            'type'=>'image',
            'user_id'=>1,
            'book_id'=>4,
            'created_at'=>Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'=>Carbon::now()->format('Y-m-d H:i:s'),

        ]);
    }
}
