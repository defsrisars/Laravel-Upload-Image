<?php

namespace Ariby\LaravelImageUpload\Rules;

use Illuminate\Contracts\Validation\Rule;
use Intervention\Image\Facades\Image;

class CheckImageWidth implements Rule
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
        if($img->width() > config('laravel_image_upload.image_max_width')){
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
        $image_max_width = config('laravel_image_upload.image_max_width');

        return "圖片寬不可超過 {$image_max_width}";
    }
}