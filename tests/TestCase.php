<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Logaretm\Transformers\Contracts\Transformable;
use Logaretm\Transformers\Transformer;
use Logaretm\Transformers\TransformerTrait;
use Illuminate\Database\Capsule\Manager as DB;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->setUpDatabase();
        $this->migrateTables();
    }

    /**
     * Sets up a sqlite in memory database.
     */
    protected function setUpDatabase()
    {
        $database = new DB;

        $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $database->bootEloquent();
        $database->setAsGlobal();
    }

    /**
     * Migrates the tables required for testing.
     */
    protected function migrateTables()
    {
        DB::schema()->create('users', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        DB::schema()->create('posts', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('title');
            $table->text('body');
            $table->unsignedInteger('user_id')->index();
            $table->timestamps();
        });

        DB::schema()->create('tags', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        DB::schema()->create('post_tag', function (Blueprint $table)
        {
            $table->unsignedInteger('tag_id')->index();
            $table->unsignedInteger('post_id')->index();
        });

        DB::schema()->create('categories', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @param int $count
     * @param bool $save
     * @return array|User
     */
    protected function makeUsers($count = 1, $save = false)
    {
        $faker = Faker\Factory::create();
        $users = [];

        for($i = 0; $i < $count; $i++)
        {
            $user = new User();
            $user->name = $faker->name;
            $user->email = $faker->email;

            if($save)
            {
                $user->save();
            }

            $users[] = $user;
        }

        return $count === 1 ? $users[0] : $users;
    }

    /**
     * @param int $count
     * @return array
     */
    protected function makeTags($count = 10)
    {
       $faker = Faker\Factory::create();

        $tagIds = [];
        foreach(range(1, $count, 1) as $value)
        {
            $tag = new Tag();
            $tag->name = $faker->word;
            $tag->save();

            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }

    /**
     * @param int $count
     * @param bool $save
     * @return array
     */
    protected function makePosts($count = 1, $save = false)
    {
        $faker = Faker\Factory::create();
        $posts = [];

        for($i = 0; $i < $count; $i++)
        {
            $post = new Post();
            $post->title = $faker->title;
            $post->body = $faker->sentence;

            if($save)
            {
                $post->save();
            }

            $posts[] = $post;
        }

        return $count === 1 ? $posts[0] : $posts;
    }

    /**
     * @return User
     */
    protected function makeUserWithPosts()
    {
        $user = $this->makeUsers(1, true);
        $posts = $user->posts()->saveMany($this->makePosts(3, false));

        foreach($posts as $post)
        {
            $post->tags()->attach($this->makeTags(4));
        }

        return $user;
    }
}