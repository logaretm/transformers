<?php

use Illuminate\Database\Eloquent\Model;
use Logaretm\Transformers\Contracts\Transformable;
use Logaretm\Transformers\Transformer;
use Logaretm\Transformers\TransformerTrait;

class TagTransformer extends Transformer
{

    /**
     * @param $tag
     * @return mixed
     */
    public function getTransformation($tag)
    {
        return [
            'name' => $tag->name
        ];
    }
}

class Tag extends Model implements Transformable
{
    use TransformerTrait;

    /**
     * @var
     */
    protected $transformer = TagTransformer::class;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsToMany(Post::class);
    }
}