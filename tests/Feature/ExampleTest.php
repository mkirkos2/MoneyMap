<?php

use App\Models\User;

test('guests visiting the home page are redirected to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login', absolute: false));
});

test('authenticated users visiting the home page reach the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Dashboard'));
});
