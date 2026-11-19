<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot open or submit the change password form', function () {
    $this->get(route('password.edit'))->assertRedirect(route('login'));

    $this->put(route('password.update'), [
        'current_password' => 'password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'));
});

test('pages shown to a signed-in user are never cached, so Back after logout cannot reveal them', function () {
    $user = User::factory()->create();

    foreach ([route('password.edit'), route('dashboard'), route('expenses.index')] as $url) {
        $cacheControl = $this->actingAs($user)->get($url)->assertOk()->headers->get('Cache-Control');

        expect($cacheControl)->toContain('no-store')->toContain('private');
    }

    $this->post(route('logout'));

    $this->get(route('password.edit'))->assertRedirect(route('login'));
});

test('guest pages keep normal caching headers', function () {
    $cacheControl = $this->get(route('login'))->assertOk()->headers->get('Cache-Control');

    expect($cacheControl)->not->toContain('no-store');
});
