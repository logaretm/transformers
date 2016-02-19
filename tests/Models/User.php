<?php

use Illuminate\Database\Eloquent\Model;
use Logaretm\Transformers\Contracts\Transformable;
use Logaretm\Transformers\Transformer;
use Logaretm\Transformers\TransformableTrait;


class UserTransformer extends Transformer
{
    /**
     * @param $user
     * @return mixed
     */
    public function getTransformation($user)
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'memberSince' => $user->created_at->timestamp
        ];
    }
}

class User extends Model implements Transformable
{
    use TransformableTrait;

    /**
     * @var
     */
    protected $transformer = UserTransformer::class;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}