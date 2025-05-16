<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\MediaController;
use App\Models\Media;
use App\Models\Week;
use App\Traits\MediaTraits;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class deleteOldMedia extends Command
{
    use MediaTraits;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:deleteOld';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete media files older than 2 weeks from the public folder';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            //get last week
            $lastWeek = Week::orderBy('id', 'desc')
                ->skip(2)
                ->take(1)
                ->first();

            //the period is backwards
            $startDate = Date('Y-m-d H:i:s', strtotime($lastWeek->created_at));
            $endDate = Date('Y-m-d H:i:s', strtotime($lastWeek->created_at . ' - 1 month'));

            $this->deleteUntrackedImagesFromThesesByWeek($startDate, $endDate);
            Log::channel('media')->info('================================================================================================================');

            Log::channel('media')->info("START deleting media files from $startDate till $endDate");

            $allStatistics = [
                'allDeleted' => 0,
                'allNotFound' => 0,
                'allFailed' => 0,
            ];
            $index = 0;
            Media::where('media', 'LIKE', "theses/%")
                ->whereNotNull('comment_id')
                ->where('created_at', '<', $startDate)
                ->where('created_at', '>', $endDate)
                ->chunkById(2000, function ($records) use (&$index, &$allStatistics) {
                    $index++;
                    $statistics = [
                        'deleted' => 0,
                        'notFound' => 0,
                        'failed' => 0,
                    ];

                    Log::channel('media')->info('START processing chunk ' . $index);

                    foreach ($records as $media) {
                        $status = $this->deleteMedia_v2($media->media);

                        switch ($status) {
                            case -1:
                                Log::channel('media')->error('Media file does not exist in the file system. Chunk ' . $index . '. Media details: ', [
                                    'media' => $media->media,
                                    'id' => $media->id,
                                ]);
                                $statistics['notFound']++;
                                break;
                            case 0:
                                Log::channel('media')->error('Error while deleting media file in chunk ' . $index . '. Media details: ', [
                                    'media' => $media->media,
                                    'id' => $media->id,
                                ]);
                                $statistics['failed']++;
                                break;
                            case 1:
                                $statistics['deleted']++;
                                break;
                        }
                    }
                    $allStatistics['allDeleted'] += $statistics['deleted'];
                    $allStatistics['allNotFound'] += $statistics['notFound'];
                    $allStatistics['allFailed'] += $statistics['failed'];

                    Log::channel('media')->info('FINISHED processing chunk ' . $index);
                    Log::channel('media')->info('Chunk statistics: ', $statistics);
                    Log::channel('media')->info('Total statistics so far: ', $allStatistics);
                    Log::channel('media')->info('================================================================================================================');
                });

            Log::channel('media')->info("FINISHED deleting media files from $startDate till $endDate");
            Log::channel('media')->info('Total statistics: ', $allStatistics);

            Log::channel('media')->info('================================================================================================================');

            Log::channel('media')->info('START deleting empty directories');
            $this->cleanupEmptyDirectories(public_path('assets/images/theses'));
            Log::channel('media')->info('FINISHED deleting empty directories');

            $this->info("Media files from $startDate till $endDate deleted successfully, along with the untracked files and empty directories");
        } catch (\Throwable $th) {
            Log::channel('media')->error('Error while deleting media files', [
                'error' => $th->getMessage() . ' in ' . $th->getFile() . ' at line ' . $th->getLine(),
            ]);

            $this->error('Error while deleting media files: ' . $th->getMessage());
        }
    }
}
