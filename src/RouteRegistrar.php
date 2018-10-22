<?php

namespace Ariby\LaravelImageUpload;

use Illuminate\Contracts\Routing\Registrar as Router;
use Illuminate\Routing\Router as RoutingRouter;

class RouteRegistrar
{
    /**
     * The router implementation.
     *
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    protected $router;

    /**
     * Create a new route registrar instance.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes for transient tokens, clients, and personal access tokens.
     *
     * @return void
     */
    public function all()
    {
        // 註冊圖片上傳的路由
        $this->router->group([], function (RoutingRouter $router) {
            $router->post('/laravel/image-upload', 'ImageUploadController@imgUpload')->name('imgUpload');
            $router->get('/image/{img}/{size?}', 'ImageUploadController@imgThumbnail')->name('imgThumbnail');
        });
    }
}
