<?php

namespace Logaretm\Transformers\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Logaretm\Transformers\Contracts\Transformable;
use Logaretm\Transformers\Transformer;
use Logaretm\Transformers\TransformableTrait;

class PostTransformer extends Transformer
{

    /**
     * @param $post
     * @return mixed
     */
    public function getTransformation($post)
    {
        return [
            'title' => $post->title,
            'body' => $post->body,
            'created' => $post->created_at->timestamp
        ];
    }
}

class Post extends Model implements Transformable
{
    use TransformableTrait;

    /**
     * @var
     */
    protected $transformer = PostTransformer::class;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
