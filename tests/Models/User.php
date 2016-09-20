<?php

namespace Logaretm\Transformers\Tests\Models;

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

    // Custom/Alternate transformation.
    public function adminTransformation($user)
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'memberSince' => $user->created_at->timestamp,
            'isAdmin' => $user->id === 1
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

    /**
     * Removes the property from the user object, for testing purposes.
     */
    public function unsetTransformerProperty()
    {
       unset($this->transformer);
    }

    /**
     * [getIsOfAgeAttribute description]
     * @return [type] [description]
     */
    public function getIsOfAgeAttribute()
    {
        return true;
    }
}
