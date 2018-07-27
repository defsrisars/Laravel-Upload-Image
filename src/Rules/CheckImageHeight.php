<?php

namespace Ariby\LaravelImageUpload\Rules;

use Illuminate\Contracts\Validation\Rule;
use Intervention\Image\Facades\Image;

class CheckImageHeight implements Rule
{
    /**
     * 判斷驗證規則是否通過。
     *
     * @param  string  $attribute
     * @param  mixed  $image
     * @return bool
     */
    public function passes($attribute, $image)
    {
        $img = Image::make($image);
        if($img->height() > config('laravel_image_upload.image_max_height')){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 取得驗證錯誤訊息。
     *
     * @return string
     */
    public function message()
    {
        $image_max_height = config('laravel_image_upload.image_max_height');

        return "圖片高不可超過 {$image_max_height}";
    }
}