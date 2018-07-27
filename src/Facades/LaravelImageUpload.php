<?php

namespace Ariby\LaravelImageUpload\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelImageUpload extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        // 回傳 alias 的名稱 (若要分隔只能用底線)
        return 'laravel_image_upload';
    }

}