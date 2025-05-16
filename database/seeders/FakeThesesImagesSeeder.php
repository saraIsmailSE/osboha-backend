<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as Driver;

class FakeThesesImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manager = new ImageManager(Driver::class);
        // $manager = ImageManager::imagick();

        $directory = public_path('assets/images/theses');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $faker = \Faker\Factory::create();

        $numImages = 100;

        for ($i = 0; $i < $numImages; $i++) {
            $fileName = 'thesis_' . uniqid() . '.jpg';
            $filePath = $directory . '/';
            $randomBookId = rand(1, 100);
            $randomUserId = rand(1, 100);

            $filePath .= "/$randomBookId/$randomUserId";

            if (!File::exists($filePath)) {
                File::makeDirectory($filePath, 0755, true);
            }

            $filePath .= '/' . $fileName;

            // Create a random image
            $image = $manager->create(600, 400)->fill($this->randomColor())
                ->text($faker->words(2, true), 150, 100, function ($font) {
                    $font->size(48);
                    $font->color('#000000');
                    $font->align('center');
                    $font->valign('center');
                })->text($faker->sentence(3), 150, 120, function ($font) {
                    $font->size(24);
                    $font->color('#ffffff');
                    $font->align('center');
                    $font->valign('center');
                });
            $image->save($filePath, 100);


            //modify the file's last modified time
            $randomDate = $faker->dateTimeBetween('-1 year', 'now');
            $mTime = $randomDate->getTimestamp();
            touch($filePath, $mTime);

            //modify the modified time within the last 14 days
            // $mTime = now()->subSeconds(rand(0, 14 * 24 * 60 * 60))->timestamp;
            // touch($filePath, $mTime);
        }

        $this->command->info("Fake thesis images created in $directory");
    }

    private function randomColor()
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
}
