<?php

namespace Logaretm\Transformers\Providers;

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
            __DIR__.'/../config/transformers.php' => config_path('transformers.php')
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
        foreach (config('transformers.transformers') as $transformerClass)
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
        return array_values(config('transformers.transformers'));
    }
}
