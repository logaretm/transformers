<?php

namespace Logaretm\Transformers\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class TransformerServiceProvider extends ServiceProvider
{
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
        $this->registerTransformers();
    }

    /**
     *
     */
    protected function registerTransformers()
    {
        if(! config()->has('transformers.transformers'))
        {
            return;
        }

        foreach (config('transformers.transformers') as $class => $transformerClass)
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
        if(! config()->has('transformers.transformers'))
        {
            return [];
        }

        return array_values(config('transformers.transformers'));
    }
}
