<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $this->withSession(['pre_authentication' => 'present']);
    $sessionIdBeforeLogin = $this->app['session']->getId();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect($this->app['session']->getId())->not->toBe($sessionIdBeforeLogin);
    expect($this->app['session']->get('pre_authentication'))->toBe('present');
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);
});

test('users cannot authenticate with an unknown email address', function () {
    $response = $this->post(route('login.store'), [
        'email' => 'unknown@example.com',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['logout_probe' => 'present']);
    $this->app['session']->start();
    $sessionIdBeforeLogout = $this->app['session']->getId();
    $csrfTokenBeforeLogout = $this->app['session']->token();

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
    expect($this->app['session']->getId())->not->toBe($sessionIdBeforeLogout);
    expect($this->app['session']->token())->not->toBe($csrfTokenBeforeLogout);
    expect($this->app['session']->get('logout_probe'))->toBeNull();
});

test('logout requires an authenticated user', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
