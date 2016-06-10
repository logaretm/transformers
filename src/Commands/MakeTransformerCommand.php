<?php

namespace Logaretm\Transformers\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeTransformerCommand extends GeneratorCommand
{
    /**
     * @var string
     */
    protected $signature = "make:transformer {name : The name of the transformer}";

    /**
     * @var string
     */
    protected $description = "Generates a transformer class boilerplate";

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../stubs/transformer.stub';
    }

    /**
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\Transformers';
    }
}