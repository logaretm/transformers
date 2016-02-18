<?php


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

        var_dump($transformedData['posts']);

        $this->assertCount(3, $transformedData['posts']);
    }

    /** @test */
    function it_transforms_nested_relations()
    {
        $this->makeUserWithPosts();

        $user = User::first();
        $transformer = new UserTransformer();

        $transformedData = $transformer->with('posts.tags')->transform($user);

        var_dump($transformedData['posts'][0]);

        $this->assertCount(3, $transformedData['posts']);
        $this->assertCount(4, $transformedData['posts'][0]['tags']);
    }
}