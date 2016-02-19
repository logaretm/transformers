<?php


use Illuminate\Database\Eloquent\Model;
use Logaretm\Transformers\Contracts\Transformable;
use Logaretm\Transformers\Exceptions\TransformerException;
use Logaretm\Transformers\Transformer;
use Logaretm\Transformers\TransformerTrait;

class TransformableTest extends TestCase
{
    /** @test */
    function it_retrieves_the_transformer_instance_from_the_model()
    {
        $user = $this->makeUsers(1);
        $transformer = $user->getTransformer();

        $this->assertInstanceOf(Transformer::class, $transformer);
    }

    /** @test */
    function it_throws_an_exception_if_a_model_uses_the_trait_but_does_not_define_the_transformer()
    {
        $model = new BadModel;
        // $this->setExpectedException(TransformerException::class);
        $this->expectException(TransformerException::class);

        // fails, throws a TransformerException.
        $model->getTransformer();
    }

    /** @test */
    function it_throws_an_exception_if_a_model_uses_the_trait_but_defines_an_invalid_transformer_class()
    {
        $model = new WorseModel;

        // $this->setExpectedException(TransformerException::class);
        $this->expectException(TransformerException::class);

        $model->getTransformer();
    }
}


class BadModel extends Model implements Transformable
{
    use TransformerTrait;
}

class WorseModel extends Model implements Transformable
{
    use TransformerTrait;

    protected $transformer = User::class;
}