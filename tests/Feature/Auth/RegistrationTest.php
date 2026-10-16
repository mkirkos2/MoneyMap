<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    Notification::fake();

    $this->withSession(['pre_registration' => 'present']);
    $sessionIdBeforeRegistration = $this->app['session']->getId();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    $this->assertModelExists($user);
    expect($user->password)->not->toBe('password');
    expect(Hash::check('password', $user->password))->toBeTrue();
    expect($this->app['session']->getId())->not->toBe($sessionIdBeforeRegistration);
    expect($this->app['session']->get('pre_registration'))->toBe('present');
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('registration rejects invalid profile and password values', function () {
    $response = $this->post(route('register.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('registration rejects an email address that is already in use', function () {
    $existingUser = User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Another User',
        'email' => $existingUser->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});
