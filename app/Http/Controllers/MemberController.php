<?php

namespace App\Http\Controllers;

use App\Enums\LedgerEntryType;
use App\Models\PendingMember;
use App\Models\User;
use App\Services\Ledger;
use App\Services\MemberImportService;
use App\Services\MemberVerification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function __construct(private MemberVerification $verification) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_no', 'like', "%{$search}%")
            ))
            ->orderBy('is_active', 'desc')
            ->orderByRaw('CAST(student_no AS UNSIGNED) ASC, student_no ASC')
            ->get();

        $allUsers = User::all();

        return view('members', [
            'pending' => PendingMember::with('creator')->where('expires_at', '>', now())->orderBy('expires_at')->get(),
            'users' => $users,
            'search' => $search,
            'activeMembers' => $allUsers->where('is_active', true)->count(),
            'inactiveMembers' => $allUsers->where('is_active', false)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'student_no' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $this->verification->add($request->user(), $validated['name'], $validated['student_no'], $validated['password']);

        return back()->with('status', "{$validated['name']} masuk antrean verifikasi.");
    }

    public function approve(Request $request, PendingMember $pending): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        try {
            $user = $this->verification->approve($pending, $request->user());
        } catch (AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "{$user->name} diverifikasi · entri ACCOUNT_VERIFIED ditulis ke ledger.");
    }

    public function reject(Request $request, PendingMember $pending): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        try {
            $this->verification->reject($pending, $request->user());
        } catch (AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "{$pending->name} ditolak · data pending dihapus, ledger tidak berubah.");
    }

    public function toggleStatus(User $user, Request $request, Ledger $ledger): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak dapat mengubah status akun sendiri.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        if (! $user->is_active) {
            $ledger->append(
                LedgerEntryType::AccountDeactivated,
                [
                    'user_id' => $user->id,
                    'student_no' => $user->student_no,
                    'name' => $user->name,
                ],
                $request->user(),
                'users',
                $user->id,
            );
        }

        $statusText = $user->is_active ? 'diaktifkan kembali' : 'dinonaktifkan dan dicatat ke ledger';

        return back()->with('status', "Akun {$user->name} berhasil {$statusText}.");
    }

    public function toggleStaff(User $user, Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak dapat mengubah status staff akun sendiri.');
        }

        $is_staff = $user->role && $user->role->name == 'Staff' ? true : false;

        $user->role = $is_staff ? 'user' : 'staff';
        $user->save();

        $statusText = ! $user->is_staff ? 'status staff diaktifkan kembali' : 'status staff dinonaktifkan';

        return back()->with('status', "Akun {$user->name} berhasil {$statusText}.");
    }

    public function resetPassword(User $user, Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat mereset password akun sendiri.');
        }

        $temporaryPassword = Str::password(16);
        $user->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        return back()
            ->with('status', "Password sementara akun {$user->name}: {$temporaryPassword} (pengguna wajib mengganti password saat login berikutnya).")
            ->with('temporary_password', $temporaryPassword);
    }

    public function import(Request $request, MemberImportService $importService): RedirectResponse
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xlsm,xls,csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Pilih file spreadsheet (.xlsx atau .csv) terlebih dahulu.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx) atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $result = $importService->import($request->file('file'), $request->user());

        $status = "{$result['success']} calon anggota berhasil diimpor ke antrean verifikasi.";
        if (! empty($result['skipped'])) {
            $status .= ' ('.count($result['skipped']).' baris dilewati/gagal).';
        }

        $redirect = back()->with('status', $status);
        if (! empty($result['skipped'])) {
            $redirect->with('import_errors', $result['skipped']);
        }

        return $redirect;
    }

    public function template(Request $request): StreamedResponse
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $filename = 'template-import-anggota.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nama', 'NIM', 'Password Awal']);
            fputcsv($handle, ['Budi Santoso', '01', 'password123']);
            fputcsv($handle, ['Siti Rahma', '02', 'password123']);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
