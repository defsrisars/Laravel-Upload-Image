<?php

namespace Ariby\LaravelImageUpload\Http\Controllers;

use Ariby\LaravelImageUpload\Http\Requests\ImageUploadRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageUploadController
{
    /**
     * 圖檔上傳
     *
     * @param ImageUploadRequest $request
     * @return bool
     */
    public function imgUpload(ImageUploadRequest $request)
    {
        // 上傳之檔案
        $file = $request->file('file');

        // 使用之硬碟名稱
        $storageName = config('laravel_image_upload.disk');

        // 判斷檔名是否重覆
        do {
            // 以 __temp__ + str_random(40) 建立暫時檔名
            $tempRandomName = "__temp__" . str_random(40);
            $basename = $tempRandomName . '.' . $file->guessExtension();
        } while (Storage::disk($storageName)->exists($basename) || Storage::disk($storageName)->exists(ltrim($basename, '__temp__')));

        // 將檔案先上傳
        $path = $file->storeAs('', $basename, $storageName);
        $realPath = config("filesystems.disks.$storageName.root") . DIRECTORY_SEPARATOR . basename($path);

        // 將檔案縮圖
        $image = Image::make($realPath);
        $height = $image->height();
        $width = $image->width();

        $maxHeight = config('laravel_image_upload.image_max_height');
        $maxWidth = config('laravel_image_upload.image_max_width');

        if ($height > $width) {
            if ($height > $maxHeight) {
                $height = $maxHeight;
                $width = ($maxHeight / $height) * $width;
            }
        } else {
            if ($width > $maxWidth) {
                $width = $maxWidth;
                $height = $height * ($maxWidth / $width);
            }
        }
        $image->resize($width, $height);
        $image->save();

        // 取得檔案公開路徑回傳
        $url = asset(Storage::url(config('laravel_image_upload.image_dir') . DIRECTORY_SEPARATOR . $basename));

        return response()
            ->json([
                'url' => $url,
                'basename' => $basename
            ])
            ->setStatusCode(Response::HTTP_OK);
    }

}