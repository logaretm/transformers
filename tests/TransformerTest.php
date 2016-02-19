<?php


use Logaretm\Transformers\Exceptions\TransformerException;

class TransformerTest extends TestCase
{
    /** @test */
    function it_transforms_a_single_model_instance()
    {
        $user = $this->makeUsers(1, true);
        $transformer = new UserTransformer();

        $this->assertEquals([
            'name' => $user->name,
            'email' => $user->email,
            'memberSince' => $user->created_at->timestamp
        ], $transformer->transform($user));
    }

    /** @test */
    function it_transformers_a_collection_of_the_model()
    {
        $this->makeUsers(10, true);
        $transformer = new UserTransformer();
        $users = User::get();

        // make sure its the same count as the created users.
        $this->assertCount(10, $transformer->transform($users));
    }

    /** @test */
    function it_transforms_a_paginator_of_the_model()
    {
        $this->makeUsers(15, true);
        $transformer = new UserTransformer();
        $users = User::paginate(5);

        $this->assertCount(5, $transformer->transform($users));
    }

    /** @test */
    function it_transforms_related_models()
    {
        $this->makeUserWithPosts();

        $user = User::first();
        $transformer = new UserTransformer();

        $transformedData = $transformer->with('posts')->transform($user);

        $this->assertCount(3, $transformedData['posts']);
    }

    /** @test */
    function it_transforms_nested_relations()
    {
        $this->makeUserWithPosts();

        $user = User::first();
        $transformer = new UserTransformer();

        $transformedData = $transformer->with('posts.tags')->transform($user);

        $this->assertCount(3, $transformedData['posts']);
        $this->assertCount(4, $transformedData['posts'][0]['tags']);
    }

    /** @test */
    function it_throws_an_exception_if_a_requested_transformation_does_not_exist()
    {
        $this->makeUsers(1, true);
        $transformer = new UserTransformer();

        $this->expectException(TransformerException::class);
        $transformer->setTransformation('public');
    }

    /** @test */
    function it_uses_multiple_transformations()
    {
        $user = $this->makeUsers(1, true);
        $transformer = new UserTransformer();

        $this->assertArrayNotHasKey('isAdmin', $transformer->transform($user));
        $transformer->setTransformation('admin');

        $this->assertArrayHasKey('isAdmin', $transformer->transform($user));
    }
}