<?php

use Tests\Models\User;

// ---------------------------------------------------------------------------
// create
// ---------------------------------------------------------------------------

it('creates a model and assigns an id', function () {
    $user = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->not->toBeNull()
        ->and($user->name)->toBe('Emeka')
        ->and($user->email)->toBe('emeka@example.com')
        ->and($user->created_at)->not->toBeNull()
        ->and($user->updated_at)->not->toBeNull();
});

it('does not fill non-fillable attributes', function () {
    $user = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com', 'admin' => true]);

    expect($user->admin)->toBeNull();
});

// ---------------------------------------------------------------------------
// find
// ---------------------------------------------------------------------------

it('finds a model by id', function () {
    $created = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);

    $found = User::find($created->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($created->id)
        ->and($found->name)->toBe('Emeka')
        ->and($found->email)->toBe('emeka@example.com');
});

it('returns null when the record does not exist', function () {
    $found = User::find('non-existent-id');

    expect($found)->toBeNull();
});

// ---------------------------------------------------------------------------
// all
// ---------------------------------------------------------------------------

it('returns all created models', function () {
    User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);
    User::create(['name' => 'Chidi', 'email' => 'chidi@example.com']);

    $all = User::all();

    expect($all)->toHaveCount(2);
});

it('returns an empty array when no models exist', function () {
    expect(User::all())->toBe([]);
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

it('updates a model\'s attributes', function () {
    $user = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);

    $user->update(['name' => 'Emeka Mbah']);

    $refreshed = User::find($user->id);

    expect($refreshed->name)->toBe('Emeka Mbah')
        ->and($refreshed->email)->toBe('emeka@example.com');
});

it('updates the updated_at timestamp on update', function () {
    $user = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);
    $originalUpdatedAt = $user->updated_at;

    // Ensure at least one second passes so the timestamps differ.
    sleep(1);

    $user->update(['name' => 'Emeka Mbah']);

    expect($user->updated_at)->not->toBe($originalUpdatedAt);
});

// ---------------------------------------------------------------------------
// delete
// ---------------------------------------------------------------------------

it('deletes a model', function () {
    $user = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);
    $id = $user->id;

    $result = $user->delete();

    expect($result)->toBeTrue()
        ->and(User::find($id))->toBeNull();
});

it('removes the record from the index on delete', function () {
    $user = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);

    $user->delete();

    expect(User::all())->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// save
// ---------------------------------------------------------------------------

it('saves a manually constructed model', function () {
    $user = new User();
    $user->fill(['name' => 'Emeka', 'email' => 'emeka@example.com']);
    $user->save();

    $found = User::find($user->id);

    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('Emeka');
});

// ---------------------------------------------------------------------------
// toArray
// ---------------------------------------------------------------------------

it('converts a model to an array', function () {
    $user = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);

    $array = $user->toArray();

    expect($array)->toHaveKey('id')
        ->toHaveKey('name', 'Emeka')
        ->toHaveKey('email', 'emeka@example.com')
        ->toHaveKey('created_at')
        ->toHaveKey('updated_at');
});
