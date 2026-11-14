<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

uses(RefreshDatabase::class);

test('guests see the public dashboard instead of the login page', function () {
    $this->get('/')->assertOk()->assertSee('Masuk');
});

test('a member logs in with student number and password', function () {
    $user = User::factory()->create(['student_no' => '2024001']);

    $this->post('/login', ['student_no' => '2024001', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('seeded admins can log in with the initial password but must change it first', function () {
    $this->seed();

    foreach (['admin1', 'admin2'] as $studentNo) {
        $this->post('/login', ['student_no' => $studentNo, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->get('/')->assertRedirect(route('password.edit'));

        auth()->logout();
    }
});

test('a wrong password is refused and explains why on the login page', function () {
    User::factory()->create(['student_no' => '2024001']);

    $this->post('/login', ['student_no' => '2024001', 'password' => 'nope'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('student_no')
        ->assertSessionHas('error', 'No. absen / NIM atau password salah.');

    $this->assertGuest();
});

test('a deactivated account cannot log in and sees the same safe error', function () {
    User::factory()->create(['student_no' => '2024001', 'is_active' => false]);

    $this->post('/login', ['student_no' => '2024001', 'password' => 'password'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('student_no')
        ->assertSessionHas('error', 'No. absen / NIM atau password salah.');
});

test('incorrect login displays one error beside the student number field', function () {
    $response = $this->withSession([
        '_errors' => new ViewErrorBag,
        'error' => 'No. absen / NIM atau password salah.',
    ])->get(route('login'));

    expect(substr_count($response->getContent(), 'No. absen / NIM atau password salah.'))->toBe(1);
});

test('login validation errors are rendered beside their inputs without a duplicate banner', function () {
    $html = view('auth.login', ['errors' => (new ViewErrorBag)->put('default', new MessageBag([
        'student_no' => ['Student number is required.'],
        'password' => ['Password is required.'],
    ]))])->render();

    expect($html)->toContain('Student number is required.')
        ->and($html)->toContain('Password is required.')
        ->and(substr_count($html, 'role="alert"'))->toBe(2)
        ->and($html)->not->toContain('<ul class="list-inside list-disc space-y-1">');
});

test('login layout renders flashed authentication errors', function () {
    $this->withSession(['error' => 'No. absen / NIM atau password salah.'])
        ->get(route('login'))
        ->assertSee('No. absen / NIM atau password salah.')
        ->assertSee('Masuk');
});

test('a member on the initial password is forced to change it', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->get('/')->assertRedirect(route('password.edit'));
    $this->actingAs($user)->get('/ledger')->assertRedirect(route('password.edit'));
});

test('the change-password page renders', function () {
    $this->actingAs(User::factory()->create(['must_change_password' => true]))
        ->get('/password')->assertOk()->assertSee('Simpan password');
});

test('changing the password clears the forced change', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->put('/password', [
        'current_password' => 'password',
        'password' => 'new-secret-123',
        'password_confirmation' => 'new-secret-123',
    ])->assertRedirect(route('dashboard'));

    expect($user->refresh()->must_change_password)->toBeFalse();
    $this->actingAs($user)->get('/')->assertOk();
});

test('the new password must differ from the initial one', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->put('/password', [
        'current_password' => 'password',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');
});

test('logout ends the session', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
});

test('authenticated layout shows change password and logout links', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('password.edit'))
        ->assertSee('Keluar');

    expect(preg_replace('/\s+/', ' ', $response->getContent()))->toContain('Ganti password');
});

test('repeated failed login attempts trigger rate limiting', function () {
    User::factory()->create(['student_no' => '2024999', 'password' => 'secret123']);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['student_no' => '2024999', 'password' => 'wrong-pass'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'No. absen / NIM atau password salah.');
    }

    // 6th attempt is throttled
    $this->post('/login', ['student_no' => '2024999', 'password' => 'wrong-pass'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Terlalu banyak percobaan masuk'));
});
