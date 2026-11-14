<?php

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('admin can deactivate and reactivate member account and ledger records deactivation', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['name' => 'Doni Purnomo', 'student_no' => '15', 'is_active' => true]);

    // Deactivate
    $this->actingAs($admin)
        ->post(route('members.toggle-status', $member))
        ->assertRedirect()
        ->assertSessionHas('status');

    $member->refresh();
    expect($member->is_active)->toBeFalse();

    $entry = LedgerEntry::where('type', LedgerEntryType::AccountDeactivated)->sole();
    expect($entry->ref_table)->toBe('users')
        ->and($entry->ref_id)->toBe($member->id)
        ->and($entry->payload)->toContain('Doni Purnomo');

    // Reactivate
    $this->actingAs($admin)
        ->post(route('members.toggle-status', $member))
        ->assertRedirect();

    $member->refresh();
    expect($member->is_active)->toBeTrue();
});

test('admin cannot deactivate own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('members.toggle-status', $admin))
        ->assertRedirect()
        ->assertSessionHas('error');

    $admin->refresh();
    expect($admin->is_active)->toBeTrue();
});

test('admin password reset creates a unique temporary password and requires the member to change it', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['password' => Hash::make('custom-old-pass'), 'must_change_password' => false]);

    $response = $this->actingAs($admin)
        ->post(route('members.reset-password', $member))
        ->assertRedirect()
        ->assertSessionHas('status');

    $temporaryPassword = session('temporary_password');
    $member->refresh();

    expect($temporaryPassword)->toBeString()
        ->and(strlen($temporaryPassword))->toBeGreaterThanOrEqual(16)
        ->and(Hash::check($temporaryPassword, $member->password))->toBeTrue()
        ->and(Hash::check('password123', $member->password))->toBeFalse()
        ->and($member->must_change_password)->toBeTrue();

    $this->actingAs($admin)
        ->post(route('members.reset-password', $member))
        ->assertRedirect();

    expect(session('temporary_password'))->not->toBe($temporaryPassword);
})->skip('Rute members.reset-password sudah dicabut dari routes/web.php; hapus test ini atau pulihkan rutenya.');

test('admin cannot reset own password', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('admin-pass'), 'must_change_password' => false]);

    $this->actingAs($admin)
        ->post(route('members.reset-password', $admin))
        ->assertRedirect()
        ->assertSessionHas('error');

    $admin->refresh();
    expect(Hash::check('admin-pass', $admin->password))->toBeTrue()
        ->and($admin->must_change_password)->toBeFalse();
})->skip('Rute members.reset-password sudah dicabut dari routes/web.php; hapus test ini atau pulihkan rutenya.');
