# Transformers

This a package that provides transformers classes and traits for the Laravel eloquent models.

## Install

Via Composer

``` bash
composer require logaretm/transformers
```

## Usage

First you need to a transformer for your model. the transformer should extend the Transformer abstract class.
And provide an implementation for the ```getTransformation()``` method.

``` php
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
```

Now you can use the transformer in multiple ways, inject it in your controller method and laravel IoC should instantiate it.

```php
class UsersController extends Controller
{
    public function index(UserTransformer $transformer)
    {
        $users = User::get();

        return response()->json([
            'users' => [
                'data' => $transformer->transform($users)
            ]
        ]);
    }
}
```

#### Dynamic Transformation

You can also use the `TransformableTrait` on your model and define a `$transformer` property to be able to use the `getTransformer()` method.

```php
class User extends Model implements Transform
{
    use TransformableTrait;

    /**
     * Defines the appropiate transformer for this model.
     *
     * @var
     */
    protected $transformer = UserTransformer::class;
}
```

then you can get the transformer instance using:

```php
$user = User::first();
$transformer = $user->getTransformer(); // returns instance of UserTransformer.
```

which can be helpful if you want to dynamically transform models. but note that it will throw a `TransformerException` if the returned instance isn't an instance of `Transformer`.

#### Service Provider

You may find retrieving the transformer over and over isn't intuitive, you can use the `TransformerServiceProvider` and a config file to define an array mapping each model or any class to a transformer class.

* Add this line to `Config/app.php` in the service providers array.

`Logaretm\Transformers\Providers\TransformerServiceProvider::class`

* Run this artisan command:

`php artisan vendor:publish --provider="Logaretm\Transformers\Providers\TransformerServiceProvider" --tag="config"`

* Head over to config/transformers.php and populate the array with your model/transformer pairs.

```php
    'transformers' => [
        User::class => UserTransformerClass
    ]
```

* Now you don't need to provide the `$transformer` property anymore on your model.

Furthermore you can now use the static methods `Transformer::make` and `Transformer::canMake` to instantiate transformers for the models.

```php
$transformer = Transformer::make(User::class);
```
#### Relations

It is also possible to transform a model along with their related models using the fluent method ```with()```.

The related model transformer is resolved when:

* If the model implements the `Transformable` contract which is automated by the `TransformableTrait`. it also needs to define the `$transformer` property.

* If the service provider is registered, then it will be resolved from the config array.

otherwise the model will be transformed using a simple `toArray()` call.

```php
$transformer = new UserTransformer();
$users = User::with('posts')->get();
$data = $transformer->with('posts')->transform($users);
```

you can also transform nested relations with the same syntax.

```php
$transformer = new UserTransformer();
$users = User::with('posts.tags')->get();
$data = $transformer->with('posts')->transform($users);
```

you can reset the transformer using `$transformer->reset()` which will remove the related models from the transformation.

aside from collections you can transform a paginator, or a single object.

```php
$users = User::get();
$transformer->transform($users); // returns an array of arrays.

$paginator = User::paginate(15);
$transformer->transform($paginator); // returns an array(15).

$user = User::first();
$transformedUser =  $transformer->transform($user); // returns a single array.
```

## Testing

Use php unit for testing.
``` bash
phpunit
```

## Contributing

All contributes will be fully credited.

## Issues

If you discover any issues, email me at logaretm1@gmail.com or use the issue tracker.

## Credits

- Abdelrahman Awad [https://github.com/logaretm]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.