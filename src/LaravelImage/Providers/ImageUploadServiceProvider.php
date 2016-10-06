<?php

namespace AnkitPokhrel\LaravelImage\Providers;

use AnkitPokhrel\LaravelImage\ImageHelper;
use AnkitPokhrel\LaravelImage\ImageUploadService;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Glide\Responses\LaravelResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;

class ImageUploadServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ( ! $this->app->routesAreCached()) {
            require __DIR__ . '/../routes.php';
        }

        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('laravel-image.php'),
        ]);

        $this->registerBladeExtensions();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->bind(ImageUploadService::class, function ($app) {
            $driver = '\\AnkitPokhrel\\LaravelImage\\Adapters\\' . ucfirst(config('laravel-image.driver'));

            $adapter = $app->make($driver);

            return new ImageUploadService($adapter);
        });

        $this->app->singleton('laravelImage', function () {
            return $this->app->make(ImageHelper::class);
        });

        $this->registerGlide();
    }

    /**
     * Register glide.
     */
    protected function registerGlide()
    {
        $this->app->singleton(Server::class, function ($app) {
            // Set source filesystem
            $source = app('laravel-image-filesystem')->getDriver();

            // Set cache filesystem
            $cache = new LeagueFilesystem(
                new Local(storage_path('/cache/laravel-image'))
            );

            // Setup glide server
            return ServerFactory::create([
                'source'   => $source,
                'cache'    => $cache,
                'base_url' => config('laravel-image.route_path') . '/' . basename(config('laravel-image.upload_dir')),
                'response' => new LaravelResponseFactory(),
            ]);
        });
    }

    /**
     * Register blade templates.
     */
    protected function registerBladeExtensions()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->directive('laravelImage', function ($options) {
            return "<?php echo \\AnkitPokhrel\\LaravelImage\\LaravelImageFacade::image($options); ?>";
        });
    }
}