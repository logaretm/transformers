<?php

namespace Logaretm\Transformers;

use Logaretm\Transformers\Exceptions\TransformerException;
use Logaretm\Transformers\Transformer;

trait TransformerTrait
{
    public function getTransformer()
    {
        // If doesn't exist.
        if(! property_exists($this, 'transformer'))
        {
            throw new TransformerException('Transformer definition not found. Check transformer property if it exists');
        }

        $transformer = app($this->transformer);

        // If not a transformer instance.
        if(! $transformer instanceof Transformer)
        {
            throw new TransformerException('Model transformer not an instance of transformer class');
        }

        return $transformer;
    }
}
