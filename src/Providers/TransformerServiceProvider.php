<?php

namespace Logaretm\Transformers\Providers;

use Illuminate\Support\ServiceProvider;

class TransformerServiceProvider extends ServiceProvider
{
    /**
     * @var bool
     */
    public $defer = true;

    /**
     * @var array
     */
    protected static $transformers = [
        // Define Model => Transformer pairs here.
        // \App\User::class => \App\Transformers\UserTransformer::class
    ];

    /**
     * @return array
     */
    public static function getTransformers()
    {
        return self::$transformers;
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTransformers();
    }

    /**
     *
     */
    protected function registerTransformers()
    {
        foreach (static::$transformers as $transformerClass)
        {
            $this->app->singleton($transformerClass, new $transformerClass());
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_values(static::$transformers);
    }
}
