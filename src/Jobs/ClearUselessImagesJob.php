<?php

namespace Ariby\LaravelImageUpload\Jobs;

use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;

/**
 * 刪除都沒有使用的 temp 圖檔
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
        Artisan::call('RoutineClearUselessImages:check-and-clear');
    }
}