<?php

namespace Logaretm\Transformers\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Logaretm\Transformers\Commands\MakeTransformerCommand;
use Logaretm\Transformers\Transformer;

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
            __DIR__ . '/../config/transformers.php' => config_path('transformers.php')
        ], 'config');

        $this->commands([
            MakeTransformerCommand::class
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if (Transformer::isConfigPublished()) {
            $this->registerTransformers();
        }
    }

    /**
     *
     */
    protected function registerTransformers()
    {
        foreach (Config::get('transformers.transformers') as $class => $transformerClass) {
            $this->app->singleton($transformerClass, function () use ($transformerClass) {
                return new $transformerClass();
            });
        }
    }
}
