<?php

use Ritechoice23\Reactions\Models\Reaction;
use Ritechoice23\Reactions\Tests\Models\Comment;
use Ritechoice23\Reactions\Tests\Models\Post;
use Ritechoice23\Reactions\Tests\Models\User;

if (! function_exists('makeUser')) {
    function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'User '.uniqid(),
            'email' => uniqid('user', true).'@example.com',
        ], $overrides));
    }
}

if (! function_exists('makePost')) {
    function makePost(array $overrides = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Post '.uniqid(),
            'content' => 'Post body',
        ], $overrides));
    }
}

it('links reactions to reactor and reactable', function () {
    $user = makeUser();
    $post = makePost();

    $user->react($post, 'love');

    $reaction = Reaction::first();

    expect($reaction->reactor)->toBeInstanceOf(User::class)
        ->and($reaction->reactor->is($user))->toBeTrue()
        ->and($reaction->reactable)->toBeInstanceOf(Post::class)
        ->and($reaction->reactable->is($post))->toBeTrue();
});

it('filters reactions by type', function () {
    $user = makeUser();
    $post = makePost();
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->react($post, 'love');
    $user2->react($post, 'like');

    expect(Reaction::byType('love')->count())->toBe(1)
        ->and(Reaction::byType('like')->count())->toBe(1);
});

it('filters reactions by reactor and reactable models', function () {
    $user = makeUser();
    $post = makePost();
    $comment = Comment::create(['body' => 'First comment']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->react($post, 'love');
    $user->react($comment, 'wow');
    $user2->react($post, 'like');

    expect(Reaction::byReactor($user)->count())->toBe(2)
        ->and(Reaction::byReactor($user2)->count())->toBe(1)
        ->and(Reaction::byReactable($post)->count())->toBe(2)
        ->and(Reaction::byReactable($comment)->count())->toBe(1);
});

it('reads the configured table name', function () {
    config(['reactions.table_name' => 'reactions']);

    $reaction = new Reaction;

    expect($reaction->getTable())->toBe('reactions');
});

it('eager loads reactor and reactable relationships', function () {
    $user = makeUser();
    $post = makePost();
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->react($post, 'love');
    $user2->react($post, 'like');

    $reactions = Reaction::with(['reactor', 'reactable'])->get();

    expect($reactions)
        ->toHaveCount(2)
        ->and($reactions->first()->relationLoaded('reactor'))->toBeTrue()
        ->and($reactions->first()->relationLoaded('reactable'))->toBeTrue();
});

it('records timestamps when reacting', function () {
    $user = makeUser();
    $post = makePost();

    $user->react($post, 'love');

    $reaction = Reaction::first();

    expect($reaction->created_at)->not->toBeNull()
        ->and($reaction->updated_at)->not->toBeNull();
});

it('does not cascade delete when a reactor is removed', function () {
    $user = makeUser();
    $post = makePost();

    $user->react($post, 'love');

    expect(Reaction::count())->toBe(1);

    $user->delete();

    expect(Reaction::count())->toBe(1);
});

it('does not cascade delete when a reactable is removed', function () {
    $user = makeUser();
    $post = makePost();

    $user->react($post, 'love');

    expect(Reaction::count())->toBe(1);

    $post->delete();

    expect(Reaction::count())->toBe(1);
});

it('supports complex filtering with multiple scopes', function () {
    $user = makeUser();
    $post = makePost();
    $post2 = makePost();
    $user2 = makeUser();

    $user->react($post, 'love');
    $user->react($post2, 'like');
    $user2->react($post, 'love');

    $loveOnPost1 = Reaction::byType('love')->byReactable($post)->count();
    $allByUser1 = Reaction::byReactor($user)->count();

    expect($loveOnPost1)->toBe(2)
        ->and($allByUser1)->toBe(2);
});
