<?php

namespace Ariby\LaravelImageUpload;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Route;

class LaravelImageUpload
{
    /**
     * constructor.
     */
    public function __construct()
    {

    }

    /**
     * 註冊服務路由
     *
     * @param callable|null $callback
     * @param array $options
     * @return void
     */
    public static function routes($callback = null, array $options = [])
    {
        $callback = $callback ?: function ($router) {
            $router->all();
        };
        $defaultOptions = [
            'namespace' => '\Ariby\LaravelImageUpload\Http\Controllers',
        ];
        $options = array_merge($defaultOptions, $options);
        Route::group($options, function ($router) use ($callback) {
            $callback(new RouteRegistrar($router));
        });
    }

}