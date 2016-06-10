<?php

namespace Logaretm\Transformers;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Logaretm\Transformers\Contracts\Transformable;
use Logaretm\Transformers\Exceptions\TransformerException;

abstract class Transformer
{
    /**
     * @var string
     */
    protected $transformationMethod = null;

    /**
     * The relations to be added to the transformation.
     *
     * @var array
     */
    protected $related = [];

    /**
     * @var int
     */
    protected $relatedCount = 0;

    /**
     * Transforms the object.
     * Called recursively when transforming a collection or a relation.
     *
     * @param $object
     * @return array|mixed
     */
    public function transform($object)
    {
        if (($collection = $this->normalize($object)) instanceof Collection) {
            return $this->transformCollection($collection);
        }

        // If there are relations setup, transform it along with the object.
        if ($this->relatedCount) {
            return $this->transformWithRelated($object);
        }

        // If another transformation method was requested, use that instead.
        if ($this->transformationMethod) {
            return $this->getAlternateTransformation($object);
        }

        return $this->getTransformation($object);
    }

    /**
     * Normalizes the object to a collection if it is some sort of a container to multiple items.
     *
     * @param $object
     * @return Collection
     */
    protected function normalize($object)
    {
        // If its a paginator instance, create a collection with its items.
        if ($object instanceof Paginator) {
            return collect($object->items());
        } elseif (is_array($object)) {
            // If its an array, package it in a collection.
            return collect($object);
        }

        return $object;
    }

    /**
     * Calls the selected alternate transformation method.
     *
     * @param $item
     * @return mixed
     */
    public function getAlternateTransformation($item)
    {
        return $this->{$this->transformationMethod}($item);
    }

    /**
     * @param $item
     * @return mixed
     */
    abstract public function getTransformation($item);

    /**
     * Transforms the item with its related models.
     *
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
        $this->reset();

        if (func_num_args() > 1) {
            return $this->with(func_get_args());
        }

        if (is_array($relation)) {
            $this->related = array_merge($this->related, $relation);
        } else {
            $this->related[] = $relation;
        }

        $this->relatedCount = count($this->related);

        return $this;
    }

    /**
     * Sets the current transformation.
     *
     * @param $transformationName
     * @return $this
     * @throws TransformerException
     */
    public function setTransformation($transformationName)
    {
        // just to avoid wrongly passing the name suffixed with "Transformation".
        $transformationName = str_replace('Transformation', '', $transformationName);
        $methodName = $transformationName . "Transformation";

        if (! method_exists($this, $methodName)) {
            throw new TransformerException("No such transformation as $methodName defined.");
        }

        $this->transformationMethod = $methodName;

        return $this;
    }

    /**
     * Transforms a collection.
     *
     * @param \Illuminate\Support\Collection $collection
     * @return array
     */
    public function transformCollection(Collection $collection)
    {
        // Use collection's built in map method (glorified map).

        return $collection->map([$this, 'transform'])->all();
    }

    /**
     * Transforms the related item, and adds it to the transformation array.
     *
     * @param $itemTransformation
     * @param $item
     * @return array
     */
    protected function transformRelated($itemTransformation, $item)
    {
        foreach ($this->related as $relation) {
            // get direct relation name.
            $relationName = explode('.', $relation, 2)[0];
            $itemTransformation[$relationName] = $this->getRelatedTransformation($item, $relation);
        }

        return $itemTransformation;
    }

    /**
     * Resolves the transformation for the related model.
     *
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

        // if its a collection switch the object to the first item.
        if ($result instanceof Collection && count($related)) {
            $result = $result[0];
        }

        $transformer = $this->resolveTransformer($result);

        // If no transformer was resolved.
        if (! $transformer) {
            $related->toArray();
        }

        // if it has nested relations (equal to or more than 2 levels)
        if (count($nestedRelations) == 2) {
            // configure the remaining nested relations to the transformer.
            $transformer->with($nestedRelations[1]);
        }

        return $transformer->transform($related);
    }

    /**
     * @param $model
     * @return Transformer|null
     */
    protected function resolveTransformer($model)
    {
        // if its a transformable model resolve its transformer.
        $className = get_class($model);

        // if its registered by the service provider.
        if (static::canMake($className)) {
            return static::make($className);
        }

        if ($model instanceof Transformable) {
            return $model->getTransformer();
        }

        return null;
    }

    /**
     * Resets the transformer relations and the selected transformation method.
     *
     * @return $this
     */
    public function reset()
    {
        return $this->resetRelations()
                    ->resetTransformation();
    }

    /**
     * Resets the relations.
     *
     * @return $this
     */
    public function resetRelations()
    {
        $this->related = [];
        $this->relatedCount = 0;

        return $this;
    }

    /**
     * Resets the transformation method to the default one.
     *
     * @return $this
     */
    public function resetTransformation()
    {
        $this->transformationMethod = null;

        return $this;
    }

    /**
     * Makes (resolves) a transformer for the given model class name.
     *
     * @param $modelName
     * @return App
     */
    public static function make($modelName)
    {
        if (! static::isConfigPublished()) {
            return null;
        }

        $transformerName = Config::get('transformers.transformers')[$modelName];

        return App::make($transformerName);
    }

    /**
     * Checks if the a transformer for specific model can be generated.
     *
     * @param $modelName
     * @return bool
     */
    public static function canMake($modelName)
    {
        if (! static::isConfigPublished()) {
            return false;
        }

        return array_has(Config::get('transformers.transformers'), $modelName);
    }

    /**
     * Checks if the app has the transformers package configuration.
     *
     * @return mixed
     */
    public static function isConfigPublished()
    {
        return Config::has('transformers.transformers');
    }
}
