<?php

namespace Logaretm\Transformers;

use Illuminate\Support\Facades\App;
use Logaretm\Transformers\Exceptions\TransformerException;

trait TransformableTrait
{
    public function getTransformer()
    {
        $modelName = get_class($this);
        $transformer  = null;

        // If doesn't exist.
        if (property_exists($this, 'transformer') && $this->transformer) {
            $transformer = App::make($this->transformer);
        }

        elseif (Transformer::canMake($modelName)) {
            $transformer = Transformer::make($modelName);
        }

        // If not a transformer instance.
        if (! $transformer instanceof Transformer) {
            throw new TransformerException('Model transformer not an instance of transformer class');
        }

        return $transformer;
    }
}
