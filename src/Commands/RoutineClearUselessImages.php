<?php

namespace Ariby\LaravelImageUpload\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class RoutineClearUselessImages extends Command
{
    // 命令名稱
    protected $signature = 'RoutineClearUselessImages:check-and-clear';

    // 說明文字
    protected $description =
        '刪除都沒有使用的 temp 圖檔，排程呼叫的時間週期就是 __temp__ 圖片有效時間';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        logger('---Clear Useless Image Job START at' . date("Y-m-d H:i:s") . '---');

        $path = Storage::disk('public')->getAdapter()->getPathPrefix();

        $files = glob($path.'__temp__*');

        // 超過一天沒有使用的圖片，刪除
        foreach($files as $file){
            $fileTime = Carbon::createFromTimestamp(Storage::lastModified('public/'.basename($file)));
            if($fileTime->diffInDays(Carbon::now()) >= 1){
                unlink($file);
            }
        }

        logger('---Clear Useless Image Job END at' . date("Y-m-d H:i:s") . '---');
    }
}