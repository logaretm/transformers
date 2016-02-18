<?php

namespace Logaretm\Transformers\Contracts;

use Logaretm\Transformers\Transformer;

interface Transformable
{
    /**
     * Should return a new transformer object for this model.
     *
     * @return Transformer
     */
    public function getTransformer();
}
