<?php

use App\Models\User;

test('appearance settings require authentication', function () {
    $this->get(route('appearance.edit'))
        ->assertRedirect(route('login'));
});

test('dark is the default appearance when no preference is stored', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get(route('appearance.edit'));

    preg_match('/<html\\b[^>]*>/', $response->getContent(), $matches);

    expect($matches[0] ?? '')
        ->toContain('class="dark"')
        ->toContain('data-appearance="dark"');
});

test('light preference is restored from the appearance cookie', function () {
    $response = $this->actingAs(User::factory()->create())
        ->withUnencryptedCookie('appearance', 'light')
        ->get(route('appearance.edit'));

    preg_match('/<html\\b[^>]*>/', $response->getContent(), $matches);

    expect($matches[0] ?? '')
        ->toContain('data-appearance="light"')
        ->not->toContain('class="dark"');
});

test('system preference remains system in the initial document', function () {
    $response = $this->actingAs(User::factory()->create())
        ->withUnencryptedCookie('appearance', 'system')
        ->get(route('appearance.edit'));

    preg_match('/<html\\b[^>]*>/', $response->getContent(), $matches);

    expect($matches[0] ?? '')
        ->toContain('data-appearance="system"')
        ->not->toContain('class="dark"');
});
