<?php


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Logaretm\Transformers\Contracts\Transformable;
use Logaretm\Transformers\Exceptions\TransformerException;
use Logaretm\Transformers\Tests\Models\User;
use Logaretm\Transformers\Tests\Models\UserTransformer;
use Logaretm\Transformers\Transformer;
use Logaretm\Transformers\TransformableTrait;

class TransformableTest extends TestCase
{

    /** @test */
    function it_retrieves_the_transformer_instance_from_the_model()
    {
        $user = $this->makeUsers(1);

        App::shouldReceive('make')
            ->once()
            ->with(UserTransformer::class)
            ->andReturn(new UserTransformer);

        $transformer = $user->getTransformer();

        $this->assertInstanceOf(UserTransformer::class, $transformer);
    }

    /** @test */
    function it_throws_an_exception_if_a_model_uses_the_trait_but_does_not_define_the_transformer()
    {
        $model = new BadModel;

        // Mock config call.
        Config::shouldReceive('has')
              ->once()
              ->with('transformers.transformers')
              ->andReturn(false);

        // $this->setExpectedException(TransformerException::class);
        $this->expectException(TransformerException::class);

        // fails, throws a TransformerException.
        $model->getTransformer();
    }

    /** @test */
    function it_throws_an_exception_if_a_model_uses_the_trait_but_defines_an_invalid_transformer_class()
    {
        $model = new AnotherBadModel;

        App::shouldReceive('make')
            ->once()
            ->with(User::class)
            ->andReturn(new User());

        // $this->setExpectedException(TransformerException::class);
        $this->expectException(TransformerException::class);

        $model->getTransformer();
    }

    /** @test */
    function it_instantiates_the_transformer_if_a_model_uses_the_trait_and_was_defined_in_the_config()
    {
        $user = $this->makeUsers(1, true);
        $user->unsetTransformerProperty();

        Config::shouldReceive('has')
            ->twice()
            ->with('transformers.transformers')
            ->andReturn(true);

        Config::shouldReceive('get')
            ->twice()
            ->with('transformers.transformers')
            ->andReturn([User::class => UserTransformer::class]);

        App::shouldReceive('make')
            ->once()
            ->with(UserTransformer::class)
            ->andReturn(new UserTransformer);

        $this->assertInstanceOf(UserTransformer::class, $user->getTransformer());
    }
}


class BadModel extends Model implements Transformable
{
    use TransformableTrait;
}

class AnotherBadModel extends Model implements Transformable
{
    use TransformableTrait;

    protected $transformer = User::class;
}