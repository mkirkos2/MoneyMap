<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

test('guest cannot access categories', function () {
    $response = $this->get(route('categories.index'));
    $response->assertRedirect(route('login'));
});

test('guest cannot create or modify categories', function () {
    $category = Category::factory()->for($this->user)->create();

    $this->get(route('categories.create'))->assertRedirect(route('login'));
    $this->post(route('categories.store'), ['name' => 'Guest category', 'type' => 'income'])
        ->assertRedirect(route('login'));
    $this->get(route('categories.edit', $category))->assertRedirect(route('login'));
    $this->put(route('categories.update', $category), ['name' => 'Changed', 'type' => 'income'])
        ->assertRedirect(route('login'));
    $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));

    $this->assertDatabaseMissing('categories', ['name' => 'Guest category']);
    expect($category->fresh()->name)->toBe($category->name);
});

test('user can view categories index', function () {
    $this->actingAs($this->user)->get(route('categories.index'))
        ->assertInertia(fn ($page) => $page
            ->component('categories/Index')
            ->has('categories.data', 0)
            ->where('categories.total', 0));
});

test('user can view only their own categories', function () {
    $ownCategory = Category::factory()->for($this->user)->create();
    $otherCategory = Category::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)->get(route('categories.index'));
    $response->assertInertia(fn ($page) => $page
        ->where('categories.total', 1)
        ->where('categories.data.0.id', $ownCategory->id)
        ->where('categories.data.0.name', $ownCategory->name)
        ->missing('categories.data.1'));
});

test('user can create category', function () {
    $response = $this->actingAs($this->user)->get(route('categories.create'));
    $response->assertOk();

    $categoryData = [
        'name' => 'Test Category',
        'type' => 'income',
        'color' => '#FF0000',
        'icon' => 'test-icon',
    ];

    $response = $this->actingAs($this->user)->post(route('categories.store'), $categoryData);
    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('toast');

    $this->assertDatabaseHas('categories', array_merge($categoryData, ['user_id' => $this->user->id]));

    $this->actingAs($this->user)->get(route('categories.index'))
        ->assertInertia(fn ($page) => $page
            ->where('categories.data.0.name', 'Test Category')
            ->where('categories.data.0.type', 'income'));
});

test('user cannot create category with invalid data', function () {
    $response = $this->actingAs($this->user)->post(route('categories.store'), []);
    $response->assertSessionHasErrors(['name', 'type']);

    $response = $this->actingAs($this->user)->post(route('categories.store'), [
        'name' => 'Test Category',
        'type' => 'invalid-type',
    ]);
    $response->assertSessionHasErrors(['type']);
});

test('user can edit their own category', function () {
    $category = Category::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)->get(route('categories.edit', $category));
    $response->assertOk();

    $updatedData = [
        'name' => 'Updated Category',
        'type' => 'expense',
        'color' => '#00FF00',
        'icon' => 'updated-icon',
    ];

    $response = $this->actingAs($this->user)->put(route('categories.update', $category), $updatedData);
    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('toast');

    $this->assertDatabaseHas('categories', array_merge($updatedData, ['id' => $category->id]));
});

test('user cannot edit another users category', function () {
    $category = Category::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)->get(route('categories.edit', $category));
    $response->assertForbidden();

    $response = $this->actingAs($this->user)->put(route('categories.update', $category), [
        'name' => 'Hacked Category',
        'type' => 'income',
    ]);
    $response->assertForbidden();

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
        'name' => 'Hacked Category',
    ]);
});

test('user can delete their own category', function () {
    $category = Category::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)->delete(route('categories.destroy', $category));
    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('toast');

    $this->assertSoftDeleted($category);
});

test('deleting a category used by transactions soft deletes the category and preserves the transaction', function () {
    $category = Category::factory()->for($this->user)->create();
    $transaction = Transaction::factory()->for($this->user)->for($category)->create();

    $response = $this->actingAs($this->user)->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $this->assertSoftDeleted($category);
    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'category_id' => $category->id,
        'deleted_at' => null,
    ]);
});

test('user cannot delete another users category', function () {
    $category = Category::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)->delete(route('categories.destroy', $category));
    $response->assertForbidden();

    $this->assertNotSoftDeleted($category);
});

test('categories are paginated', function () {
    // Create more categories than the pagination limit
    Category::factory()->count(20)->for($this->user)->create();

    $response = $this->actingAs($this->user)->get(route('categories.index'));
    $response->assertInertia(fn ($page) => $page
        ->component('categories/Index')
        ->has('categories.data', 15)
        ->has('categories.next_page_url')
        ->where('categories.total', 20));
});
