<?php

namespace Ariby\LaravelImageUpload;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Ariby\LaravelImageUpload\Commands\RoutineClearUselessImages;
use Illuminate\Support\Facades\Artisan;

class LaravelImageUploadServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 發佈設定檔
        $this->publishes([
            __DIR__ . '/../config/laravel_image_upload.php.php' => config_path('laravel_image_upload.php'),
        ]);

        // 建立連結符號
        Artisan::call('storage:link');

        if ($this->app->runningInConsole()) {
            // 合併套件設定檔
            $this->mergeConfigFrom(
                __DIR__ . '/../config/laravel_image_upload.php', 'laravel_image_upload'
            );
            // 執行所有套件 migrations
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            // 註冊所有 commands
            $this->commands([
                RoutineClearUselessImages::class
            ]);
        }
    }

    /**com
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 註冊 Facade 別名
        $loader = AliasLoader::getInstance();
        $loader->alias('laravel_image_upload', 'Ariby\LaravelImageUpload\LaravelImageUpload');

        // 註冊 Intervention\Image 套件
        $this->app->register(\Intervention\Image\ImageServiceProvider::class);
        $loader->alias('Image', \Intervention\Image\Facades\Image::class);

        // 註冊所有 commands
        $this->commands([
            RoutineClearUselessImages::class
        ]);
    }
}
