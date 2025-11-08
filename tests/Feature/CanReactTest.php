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

it('stores a reaction with the default type when none is provided', function () {
    $user = makeUser(['name' => 'John Doe', 'email' => 'john@example.com']);
    $post = makePost(['title' => 'Test Post']);

    $reaction = $user->react($post);

    expect($reaction)
        ->toBeInstanceOf(Reaction::class)
        ->and($reaction->reaction_type)->toBe(config('reactions.default_reaction_type'))
        ->and($post->reactionsCount())->toBe(1)
        ->and(Reaction::count())->toBe(1);
});

it('respects the configured default reaction type', function () {
    config(['reactions.default_reaction_type' => 'applause']);

    $user = makeUser();
    $post = makePost();

    $reaction = $user->react($post);

    expect($reaction->reaction_type)->toBe('applause');
});

it('updates an existing reaction instead of creating duplicates', function () {
    $user = makeUser();
    $post = makePost();

    $first = $user->react($post, 'like');
    $second = $user->react($post, 'love');

    expect($second->id)
        ->toBe($first->id)
        ->and($second->reaction_type)->toBe('love')
        ->and(Reaction::count())->toBe(1)
        ->and($user->reactionTo($post))->toBe('love');
});

it('allows a reactor to remove a reaction', function () {
    $user = makeUser();
    $post = makePost();

    $user->react($post, 'care');

    expect($user->unreact($post))
        ->toBeTrue()
        ->and($post->reactionsCount())->toBe(0)
        ->and($user->hasReactedTo($post))->toBeFalse();
});

it('reports reaction presence and type helpers', function () {
    $user = makeUser();
    $post = makePost();

    expect($user->hasReactedTo($post))->toBeFalse()
        ->and($user->reactionTo($post))->toBeNull();

    $user->react($post, 'fire');

    expect($user->hasReactedTo($post))->toBeTrue()
        ->and($user->reactionTo($post))->toBe('fire');
});

it('tracks reactions across multiple reactable types', function () {
    $user = makeUser();
    $post = makePost();
    $comment = Comment::create(['body' => 'First comment']);
    $otherPost = makePost();

    $user->react($post, 'like');
    $user->react($comment, 'celebrate');
    $user->react($otherPost, 'love');

    expect($user->reactionsGiven)->toHaveCount(3)
        ->and(Reaction::byReactor($user)->count())->toBe(3);
});

it('scopes reactors that have reacted to a model', function () {
    $post = makePost();
    $user = makeUser();
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

    $user->react($post, 'love');
    $user2->react($post, 'like');

    $reacted = User::reactedTo($post)->pluck('id');

    expect($reacted)
        ->toContain($user->id, $user2->id)
        ->and($reacted)->not->toContain($user3->id);
});

it('scopes reactors by reaction type', function () {
    $post = makePost();
    $user = makeUser();
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

    $user->react($post, 'love');
    $user2->react($post, 'like');
    $user3->react($post, 'love');

    $loveReactors = User::reactedWith($post, 'love')->pluck('id');

    expect($loveReactors)
        ->toHaveCount(2)
        ->and($loveReactors)->toContain($user->id, $user3->id)
        ->and($loveReactors)->not->toContain($user2->id);
});

it('supports any arbitrary reaction text or emoji', function () {
    $post = makePost();
    $types = ['excited', 'curious', '🔥', '💯'];

    foreach ($types as $index => $type) {
        User::create(['name' => "User {$index}", 'email' => "user{$index}@example.com"])
            ->react($post, $type);
    }

    expect(array_keys($post->reactionsBreakdown()))
        ->toEqualCanonicalizing($types);
});
