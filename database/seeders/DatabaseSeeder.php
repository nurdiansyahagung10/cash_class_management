<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the first admin and a staff account; both must change the initial password "password" on first login.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Kelas',
            'student_no' => 'admin',
            'email' => null,
            'must_change_password' => true,
        ]);

        $admin->forceFill([
            'password' => 'password',
            'email_verified_at' => now(),
        ])->save();

        User::factory()->staff()->create([
            'name' => 'Staff Kelas',
            'student_no' => 'staff',
            'email' => null,
            'must_change_password' => true,
        ]);

    }
}
