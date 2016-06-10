# Transformers
[![Build Status](https://travis-ci.org/logaretm/transformers.svg?branch=master)](https://travis-ci.org/logaretm/transformers)

This a package that provides transformer (reducer/serializer) classes and traits for the Laravel eloquent models.

## Install

Via Composer

``` bash
composer require logaretm/transformers
```

##### Transformer:
A class responsible for transforming or reducing an object from one form to another then consumed.

##### Why would you use them?

Transformers are useful in API responses, where you want the ajax results to be in a specific form, by hiding attributes, exposing additional ones, or convert attribute types.

Also by delegating the responsibility of transforming models to a separate class make it easier to handle and maintain down the line.

##### Inspiration

Having seen[Jeffery Way's Laracasts video](https://laracasts.com/series/incremental-api-development/episodes/4) and reading the book [Building APIs You Won't Hate](https://apisyouwonthate.com/), I wanted to create a simple package specific to laravel apps and because I needed this functionality in almost every project.

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

You can also instantiate it manually if you don't think DI is your thing.
```php
$transformer = new UserTransformer;
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

* Add this line to `config/app.php` in the service providers array.

`Logaretm\Transformers\Providers\TransformerServiceProvider::class`

* Run this artisan command:

`php artisan vendor:publish --provider="Logaretm\Transformers\Providers\TransformerServiceProvider" --tags="config"`

* Head over to config/transformers.php and populate the array with your model/transformer pairs.

```php
    'transformers' => [
        User::class => UserTransformerClass
    ]
```

* Now you don't need to provide the `$transformer` property anymore on your model, nor implement the interface.

Note that the transformer resolution for the related model will prioritize the registered transformers.

Furthermore you can now use the static methods `Transformer::make` and `Transformer::canMake` to instantiate transformers for the models, using the trait is still helpful, but not required anymore.

```php
if(Transformer::canMake(User::class); // returns true if the transformer is registered.
$transformer = Transformer::make(User::class); // creates a transformer for the model.
```
#### Relations

It is also possible to transform a model along with their related models using the fluent method ```with()```.

The related model transformer is resolved when:

* If the service provider is registered, then it will be resolved from the config array.

* If the model implements the `Transformable` contract which is automated by the `TransformableTrait`. it also needs to define the `$transformer` property.

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
$data = $transformer->with('posts.tags')->transform($users);
```

you can reset the transformer relations using `$transformer->resetRelations()` which will remove the related models from the transformation. also note that any call to `with` will reset the transformer automatically.

aside from collections you can transform a paginator, or a single object.

```php
$users = User::get();
$transformer->transform($users); // returns an array of arrays.

$paginator = User::paginate(15);
$transformer->transform($paginator); // returns an array(15).

$user = User::first();
$transformedUser =  $transformer->transform($user); // returns a single array.
```

#### Alternate Transformations

You don't have to use only one transformation per transformer, for example you may need specific transformations for specific scenarios for the same model.

using the method `setTransformation` you can override the transformation method to use another one you have defined on the transformer.

 ```php
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
             'isAdmin' => $user->isAdmin()
         ];
     }
 }
 ```

To use the alternate transformation:

```php
$transformer->setTransformation('admin');
```

Note that the naming convention for the transformation method is `{transformation_name}Transformation`.

any subsequent calls to `transform` method will use that transformation instead.

Note that it will throw a TransformerException if the requested transformation does not exist.

to reset the transformation method use the `resetTransformation` method.

```php
$transformer->resetTransformation(); //resets the transformation method.
```

or if you want to reset both relations and transformation method:

```php
$transformer->reset(); //resets the transformation method and the relations.
```

#### Generating Transformers
You can easily generate a transformer class using this artisan command:
```bash
php artisan make:transformer {transformer name}
```

which will create a basic transformer class in `app/Transformers` directory, don't forget to put your transformations there.

## Testing

Use php unit for testing.
``` bash
phpunit
```

## TODO

* Improve the API and method names.
* ~~Maybe a console command to generate a transformer for a model.~~
* ~~Use closures to override transformation.~~
* Write more todos.

## Contributing

All contributes will be fully credited.

## Issues

If you discover any issues, email me at logaretm1@gmail.com or use the issue tracker.

## Credits

- [Abdelrahman Awad](https://github.com/logaretm)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
