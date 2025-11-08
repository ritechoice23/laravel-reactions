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

it('exposes reactions relation and total count', function () {
    $user = makeUser();
    $post = makePost();
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->react($post, 'love');
    $user2->react($post, 'like');

    expect($post->reactions)
        ->toHaveCount(2)
        ->and($post->reactionsCount())->toBe(2)
        ->and(Reaction::count())->toBe(2);
});

it('calculates breakdown by reaction type', function () {
    $user = makeUser();
    $post = makePost();
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

    $user->react($post, 'love');
    $user2->react($post, 'like');
    $user3->react($post, 'love');

    $breakdown = $post->reactionsBreakdown();

    expect($breakdown)
        ->toBeArray()
        ->toHaveCount(2)
        ->and($breakdown['love'] ?? null)->toBe(2)
        ->and($breakdown['like'] ?? null)->toBe(1);
});

it('detects and retrieves a specific reactor', function () {
    $user = makeUser();
    $post = makePost();
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    expect($post->isReactedBy($user))->toBeFalse();
    expect($post->reactionBy($user))->toBeNull();

    $user->react($post, 'celebrate');
    $user2->react($post, 'like');

    expect($post->isReactedBy($user))->toBeTrue();
    expect($post->reactionBy($user))
        ->toBeInstanceOf(Reaction::class)
        ->and($post->reactionBy($user)->reaction_type)->toBe('celebrate');
});

it('removes a reaction for a specific reactor', function () {
    $user = makeUser();
    $post = makePost();
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->react($post, 'love');
    $user2->react($post, 'like');

    expect($post->removeReaction($user))
        ->toBeTrue()
        ->and($post->reactionsCount())->toBe(1)
        ->and($post->isReactedBy($user))->toBeFalse();
});

it('adds reactions_count via scope', function () {
    $user = makeUser();
    $post = makePost();
    $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->react($post, 'love');
    $user2->react($post, 'like');
    $user->react($post2, 'celebrate');

    $posts = Post::withReactionsCount()->orderBy('id')->get();

    expect($posts[0]->reactions_count)->toBe(2)
        ->and($posts[1]->reactions_count)->toBe(1);
});

it('orders models by most reactions', function () {
    $user = makeUser();
    $post = makePost();
    $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);
    $post3 = Post::create(['title' => 'Post 3', 'content' => 'Content 3']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

    // Post 1 => 3 reactions
    $user->react($post, 'love');
    $user2->react($post, 'like');
    $user3->react($post, 'wow');

    // Post 2 => 1 reaction
    $user->react($post2, 'love');

    // Post 3 => 2 reactions
    $user2->react($post3, 'wow');
    $user3->react($post3, 'wow');

    $topPosts = Post::mostReacted(2)->get();

    expect($topPosts)
        ->toHaveCount(2)
        ->and($topPosts->first()->id)->toBe($post->id)
        ->and($topPosts->last()->id)->toBe($post3->id);
});

it('adds reaction status metadata for a given reactor', function () {
    $user = makeUser();
    $post = makePost();
    $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);

    $user->react($post, 'love');

    $posts = Post::withReactionStatus($user)->orderBy('id')->get();

    expect($posts[0]->has_reacted)->toBe(1)
        ->and($posts[0]->reactor_reaction_type)->toBe('love')
        ->and($posts[1]->has_reacted)->toBeNull()
        ->and($posts[1]->reactor_reaction_type)->toBeNull();
});

it('handles polymorphic reactables independently', function () {
    $user = makeUser();
    $post = makePost();
    $comment = Comment::create(['body' => 'First comment']);
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->react($post, 'love');
    $user2->react($comment, 'like');

    expect($post->reactionsCount())->toBe(1)
        ->and($comment->reactionsCount())->toBe(1)
        ->and(Reaction::count())->toBe(2);
});
