<?php

namespace App\Services;

use App\Enums\LedgerEntryType;
use App\Enums\UserRole;
use App\Models\PendingMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberVerification
{
    public function __construct(private Ledger $ledger) {}

    /**
     * Staff (or admin) proposes a candidate; it expires after 3 days unless an admin verifies it.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function add(User $creator, string $name, string $studentNo, string $initialPassword): PendingMember
    {
        if (! in_array($creator->role, [UserRole::Staff, UserRole::Admin], true)) {
            throw new AuthorizationException('Only staff or admin can add candidates.');
        }

        PendingMember::where('student_no', $studentNo)
            ->where('expires_at', '<=', now())
            ->delete();

        if (User::where('student_no', $studentNo)->exists() || PendingMember::where('student_no', $studentNo)->exists()) {
            throw ValidationException::withMessages(['student_no' => 'No. absen / NIM sudah terdaftar.']);
        }

        return PendingMember::create([
            'name' => $name,
            'student_no' => $studentNo,
            'password' => $initialPassword,
            'created_by' => $creator->id,
            'expires_at' => now()->addDays(3),
        ]);
    }

    /**
     * Turn a candidate into a member and record ACCOUNT_VERIFIED in the ledger.
     *
     * @throws AuthorizationException
     */
    public function approve(PendingMember $pending, User $admin): User
    {
        $this->assertCanDecide($pending, $admin);

        if ($pending->isExpired()) {
            throw new AuthorizationException('Candidate has expired.');
        }

        return DB::transaction(function () use ($pending, $admin) {
            $user = (new User)->forceFill([
                'name' => $pending->name,
                'student_no' => $pending->student_no,
                'password' => $pending->password,
                'role' => UserRole::User,
                'is_active' => true,
                'must_change_password' => true,
            ]);
            $user->save();

            $this->ledger->append(
                LedgerEntryType::AccountVerified,
                ['user_id' => $user->id, 'student_no' => $user->student_no],
                $admin,
                'users',
                $user->id,
            );

            $pending->delete();

            return $user;
        });
    }

    /**
     * Reject a candidate; nothing was ever in the ledger, so the row is simply deleted.
     *
     * @throws AuthorizationException
     */
    public function reject(PendingMember $pending, User $admin): void
    {
        $this->assertCanDecide($pending, $admin);

        $pending->delete();
    }

    /**
     * @throws AuthorizationException
     */
    private function assertCanDecide(PendingMember $pending, User $admin): void
    {
        if ($admin->role !== UserRole::Admin) {
            throw new AuthorizationException('Only admin can verify candidates.');
        }

        if ($pending->created_by === $admin->id) {
            throw new AuthorizationException('You cannot decide on a candidate you added yourself.');
        }
    }
}
