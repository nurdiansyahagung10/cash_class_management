<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('report pdf downloads as an attachment named after the tab', function (string $role, string $tab) {
    $user = User::factory()->{$role}()->create();

    $response = $this->actingAs($user)->get(route('reports.payments.pdf', ['tab' => $tab]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))
        ->toStartWith('attachment')
        ->toContain("laporan-kas-{$tab}-")
        ->toContain('.pdf');
    expect($response->getContent())->toStartWith('%PDF');
})->with(['admin', 'staff'])->with(['detail', 'matrix', 'bku']);

test('report pdf falls back to the detail tab for an unknown tab', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('reports.payments.pdf', ['tab' => 'bogus']));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('laporan-kas-detail-');
});

test('report pdf opens inline when requested', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('reports.payments.pdf', ['tab' => 'bku', 'inline' => 1]));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline');
});

test('report pdf sends regular members back to the dashboard', function (string $tab) {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get(route('reports.payments.pdf', ['tab' => $tab]))
        ->assertRedirect(route('dashboard'));
})->with(['detail', 'matrix', 'bku']);

test('report pdf redirects guests to the login page', function () {
    $this->get(route('reports.payments.pdf', ['tab' => 'bku']))
        ->assertRedirect(route('login'));
});

test('report pdf redirects staff who must change their password', function () {
    $staff = User::factory()->staff()->create(['must_change_password' => true]);

    $this->actingAs($staff)
        ->get(route('reports.payments.pdf', ['tab' => 'matrix']))
        ->assertRedirect(route('password.edit'));
});
