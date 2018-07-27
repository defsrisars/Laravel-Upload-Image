<?php

namespace Ariby\LaravelImageUpload\Jobs;

use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * 刪除都沒有使用的 temp 圖檔，排程呼叫的時間週期就是 __temp__ 圖片有效時間
 *
 * Class ClearUselessImagesJob
 * @package Ariby\LaravelImageUpload\Jobs
 */
class ClearUselessImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        logger('---Clear Useless Image Job START at' . date("Y-m-d H:i:s") . '---');

        $disk = config('laravel_image_upload.disk');
        $path = Storage::disk($disk)->getAdapter()->getPathPrefix();
        $files = glob($path.'__temp__*');

        foreach($files as $file){
            unlink($file);
        }

        logger('---Clear Useless Image Job END at' . date("Y-m-d H:i:s") . '---');
    }
}