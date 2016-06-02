<?php

namespace Logaretm\Transformers;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
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
     * Transforms the item object.
     *
     * @param $object
     * @return mixed
     */
    public function transform($object)
    {
        if ($object instanceof Paginator) {
            return $this->transformCollection($object->items());
        }

        if ($object instanceof Collection) {
            return $this->transformCollection($object);
        }

        if (count($this->related)) {
            return $this->transformWithRelated($object);
        }

        if ($this->transformationMethod) {
            return $this->getAlternateTransformation($object);
        }

        return $this->getTransformation($object);
    }

    /**
     * Calls the selected alternate transformation method.
     *
     * @param $item
     * @return mixed
     */
    public function getAlternateTransformation($item)
    {
        return $this->{"{$this->transformationMethod}"}($item);
    }

    /**
     * @param $item
     * @return mixed
     */
    public abstract function getTransformation($item);

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
        }

        else {
            $this->related[] = $relation;
        }

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
     * @param $collection
     * @return array
     */
    public function transformCollection($collection)
    {
        $transformedCollection = [];
        foreach ($collection as $item) {
            $transformedCollection[] = $this->transform($item);
        }

        return $transformedCollection;
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
            $itemTransformation[explode('.', $relation, 2)[0]] = $this->getRelatedTransformation($item, $relation);
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

        // if its a collection get the transformer object of the first item.
        if ($result instanceof Collection && count($related)) {
            $result = $result[0];
        }

        // if its a transformable model resolve its transformer.
        if ($result instanceof Transformable) {
            $transformer = $result->getTransformer();
        }

        // if its registered by the service provider.
        elseif (static::canMake(get_class($result))) {
            $transformer = static::make(get_class($result));
        }

        // otherwise cast it to an array.

        else {
            return $related->toArray();
        }

        // if it has nested relations (equal to or more than 2 levels)
        if (count($nestedRelations) == 2) {
            // pass the remaining nested relations to the transformer.
            $transformer->with($nestedRelations[1]);
        }

        return $transformer->transform($related);
    }

    /**
     * Resets the transformer relations and the selected transformation method.
     *
     * @return $this
     */
    public function reset()
    {
        $this->related = [];
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
