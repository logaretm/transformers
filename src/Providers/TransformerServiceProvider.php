<?php

namespace Logaretm\Transformers\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Logaretm\Transformers\Transformer;

class TransformerServiceProvider extends ServiceProvider
{
    /**
     * @var bool
     */
    public $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../Config/transformers.php' => config_path('transformers.php')
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if(Transformer::isConfigPublished())
        {
            $this->registerTransformers();
        }
    }

    /**
     *
     */
    protected function registerTransformers()
    {
        foreach (Config::get('transformers.transformers') as $class => $transformerClass)
        {
            $this->app->singleton($transformerClass, function () use($transformerClass)
            {
                return new $transformerClass();
            });
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return Transformer::isConfigPublished() ? array_values(Config::get('transformers.transformers')) : [];
    }
}
