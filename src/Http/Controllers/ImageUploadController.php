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

        // 判斷檔名是否重覆
        do {
            // 以 __temp__ + str_random(40) 建立暫時檔名
            $tempRandomName = "__temp__" . str_random(40);
            $basename = $tempRandomName . '.' . $file->guessExtension();
        } while (Storage::disk('public')->exists($basename) || Storage::disk('public')->exists(ltrim($basename, '__temp__')));

        // 將檔案先上傳
        $path = $file->storeAs('', $basename, 'public');
        $realPath = config("filesystems.disks.public.root") . DIRECTORY_SEPARATOR . basename($path);

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
        $url = asset('storage' . DIRECTORY_SEPARATOR . $basename);
        $uri = 'storage' . DIRECTORY_SEPARATOR . $basename;

        return response()
            ->json([
                'url' => $url,
                'uri' => $uri,
                'basename' => $basename
            ])
            ->setStatusCode(Response::HTTP_OK);
    }

    // 圖片縮圖回傳
    function imgThumbnail($img, $size)
    {
        $bp = strpos($size, '-');
        // 沒有分隔符，表示只有 h 或 w
        if ($bp === false) {
            $hp = strpos($size, 'h=');
            // 找不到 h=
            if ($hp === false) {
                $wp = strpos($size, 'w=');
                if ($wp === false) {
                    abort(404);
                } else {
                    // 只有 w
                    $w = substr($size, $wp + 2);
                    return \Image::make(public_path("storage/{$img}"))->resize($w, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->response('jpg');
                }
            } else {
                // 只有 h=
                $h = substr($size, $hp + 2);
                return \Image::make(public_path("storage/{$img}"))->resize(null, $h, function ($constraint) {
                    $constraint->aspectRatio();
                })->response('jpg');
            }
        } else {
            // 有分隔符

            // 前半段
            $firstHalf = substr($size, 0, $bp);

            $wp = strpos($firstHalf, 'w=');
            $hp = strpos($firstHalf, 'h=');
            if ($wp === false && $hp === false) {
                abort(404);
            }
            if ($wp !== false) {
                $w = substr($firstHalf, $wp + 2);
            }
            if ($hp !== false) {
                $h = substr($firstHalf, $wp + 2);
            }

            // 後半段
            $lastHalf = substr($size, $bp + 1);

            $wp = strpos($lastHalf, 'w=');
            $hp = strpos($lastHalf, 'h=');
            if ($wp === false && $hp === false) {
                abort(404);
            }
            if ($wp !== false) {
                $w = substr($lastHalf, $wp + 2);
            }
            if ($hp !== false) {
                $h = substr($lastHalf, $wp + 2);
            }

            return \Image::make(public_path("storage/{$img}"))->resize($w, $h)->response('jpg');
        }
    }

}