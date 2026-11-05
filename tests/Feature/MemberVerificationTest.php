<?php

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\PendingMember;
use App\Models\User;
use App\Services\Ledger;
use App\Services\MemberVerification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('staff adds a candidate that expires in 3 days', function () {
    $staff = User::factory()->staff()->create();

    $pending = app(MemberVerification::class)->add($staff, 'Rina Kusuma', '29', 'secret-123');

    expect($pending->created_by)->toBe($staff->id)
        ->and($pending->expires_at->isSameDay(now()->addDays(3)))->toBeTrue()
        ->and($pending->password)->not->toBe('secret-123');
});

test('plain members cannot add candidates', function () {
    $member = User::factory()->create();

    app(MemberVerification::class)->add($member, 'X', '1', 'pw');
})->throws(AuthorizationException::class);

test('a student number already registered is refused', function () {
    $staff = User::factory()->staff()->create();
    User::factory()->create(['student_no' => '29']);

    app(MemberVerification::class)->add($staff, 'Rina', '29', 'pw');
})->throws(ValidationException::class);

test('staff can add candidate when a previous pending candidate has expired', function () {
    $staff = User::factory()->staff()->create();
    PendingMember::factory()->expired()->create(['student_no' => '29']);

    $pending = app(MemberVerification::class)->add($staff, 'Rina Baru', '29', 'secret-456');

    expect($pending->student_no)->toBe('29')
        ->and($pending->name)->toBe('Rina Baru')
        ->and(PendingMember::count())->toBe(1);
});

test('admin approval creates the member and writes ACCOUNT_VERIFIED to the ledger', function () {
    $admin = User::factory()->admin()->create();
    $pending = PendingMember::factory()->create();

    $user = app(MemberVerification::class)->approve($pending, $admin);

    $entry = LedgerEntry::sole();
    expect($user->student_no)->toBe($pending->student_no)
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and($entry->type)->toBe(LedgerEntryType::AccountVerified)
        ->and($entry->actor_id)->toBe($admin->id)
        ->and($entry->ref_id)->toBe($user->id)
        ->and(PendingMember::count())->toBe(0)
        ->and(app(Ledger::class)->firstBrokenSeq())->toBeNull();
});

test('staff cannot approve', function () {
    $staff = User::factory()->staff()->create();
    $pending = PendingMember::factory()->create();

    app(MemberVerification::class)->approve($pending, $staff);
})->throws(AuthorizationException::class);

test('an admin cannot approve a candidate they added', function () {
    $admin = User::factory()->admin()->create();
    $pending = PendingMember::factory()->create(['created_by' => $admin->id]);

    app(MemberVerification::class)->approve($pending, $admin);
})->throws(AuthorizationException::class);

test('an expired candidate cannot be approved', function () {
    $admin = User::factory()->admin()->create();
    $pending = PendingMember::factory()->expired()->create();

    app(MemberVerification::class)->approve($pending, $admin);
})->throws(AuthorizationException::class);

test('rejecting deletes the candidate and leaves the ledger untouched', function () {
    $admin = User::factory()->admin()->create();
    $pending = PendingMember::factory()->create();

    app(MemberVerification::class)->reject($pending, $admin);

    expect(PendingMember::count())->toBe(0)
        ->and(LedgerEntry::count())->toBe(0);
});

test('model:prune removes only expired candidates', function () {
    PendingMember::factory()->expired()->create();
    $fresh = PendingMember::factory()->create();

    $this->artisan('model:prune', ['--model' => [PendingMember::class]])->assertSuccessful();

    expect(PendingMember::pluck('id')->all())->toBe([$fresh->id]);
});
