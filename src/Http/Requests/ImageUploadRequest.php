<?php

namespace Ariby\LaravelImageUpload\Http\Requests;

use Ariby\LaravelImageUpload\Rules\CheckImageHeight;
use Ariby\LaravelImageUpload\Rules\CheckImageWidth;

class ImageUploadRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // 圖片最大大小
        $image_max_size = config('laravel_image_upload.image_max_size');

        return [
            'file' => [
                'required',
                'image',
                "max:{$image_max_size}", // 圖片大小，預設 4 MB
//                new CheckImageHeight, // 圖片高度限制，預設最大 8192
//                new CheckImageWidth, // 圖片寬度限制，預設最大 8192
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'files' => '圖片'
        ];
    }
}
