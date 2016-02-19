<?php

namespace Logaretm\Transformers;

use Illuminate\Support\Facades\App;
use Logaretm\Transformers\Contracts\Transformable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Logaretm\Transformers\Providers\TransformerServiceProvider;

abstract class Transformer
{

    /**
     * The relations to be added to the transformation.
     *
     * @var array
     */
    protected $related = [];

    /**
     * Transforms the item object.
     *
     * @param $object
     * @return mixed
     */
    public function transform($object)
    {
        if($object instanceof Paginator)
        {
            return $this->transformCollection($object->items());
        }

        if($object instanceof Collection)
        {
            return $this->transformCollection($object);
        }

        if(count($this->related))
        {
            return $this->transformWithRelated($object);
        }

        return $this->getTransformation($object);
    }

    /**
     * @param $item
     * @return mixed
     */
    public abstract function getTransformation($item);

    /**
     * @param $item
     * @return array
     */
    public function transformWithRelated($item)
    {
        $transformedItem = $this->getTransformation($item);

        return $this->transformRelated($transformedItem, $item);
    }

    /**
     * Adds a relation to the transformer.
     *
     * @param $relation
     * @return $this
     */
    public function with($relation)
    {
        if(func_num_args() > 1)
        {
            return $this->with(func_get_args());
        }

        if(is_array($relation))
        {
            $this->related = array_merge($this->related, $relation);
        }

        else
        {
            $this->related[] = $relation;
        }

        return $this;
    }

    /**
     * @param $collection
     * @return array
     */
    public function transformCollection($collection)
    {
        $transformedCollection = [];
        foreach($collection as $item)
        {
            $transformedCollection[] = $this->transform($item);
        }

        return $transformedCollection;
    }

    /**
     * @param $itemTransformation
     * @param $item
     * @return array
     */
    protected function transformRelated($itemTransformation, $item)
    {
        foreach($this->related as $relation)
        {
            // get direct relation name.
            $itemTransformation[explode('.', $relation, 2)[0]] = $this->getRelatedTransformation($item, $relation);
        }

        return $itemTransformation;
    }

    /**
     * @param $item
     * @param $relation
     * @return mixed
     */
    protected function getRelatedTransformation($item, $relation)
    {
        // get nested relations separated by the dot notation.
        // we only get one relation at a time because recursion handles the remaining relations.
        $nestedRelations = explode('.', $relation, 2);
        $relation = $nestedRelations[0];

        $result = $item->{$relation};
        $related = $result;


        $transformer = null;

        // if its a collection get the transformer object of the first item.
        if($result instanceof Collection && count($related))
        {
            $result = $result[0];
        }

        // if its a transformable model get its transformer.
        // otherwise cast it to an array.

        if($result instanceof Transformable)
        {
            $transformer = $result->getTransformer();
        }

        else
        {
            return $related->toArray();
        }

        // if it has nested relations (equal to or more than 2 levels)
        if(count($nestedRelations) == 2)
        {
            // pass the remaining nested relations to the transformer.
            $transformer->with($nestedRelations[1]);
        }

        return $transformer->transform($related);
    }

    /**
     * Resets the transformer relations.
     *
     * @return $this
     */
    public function reset()
    {
        $this->related = [];

        return $this;
    }

    /**
     * @param $modelName
     */
    public static function make($modelName)
    {
        $transformerName = config('transformers.transformers')[$modelName];

        return App::make($transformerName);
    }

    /**
     * @param $modelName
     * @return bool
     */
    public static function canMake($modelName)
    {
        return array_has(config('transformers.transformers'), $modelName);
    }
}
