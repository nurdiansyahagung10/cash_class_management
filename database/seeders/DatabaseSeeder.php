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
        $admina = User::factory()->admin()->create([
            'name' => 'Admin 1',
            'student_no' => 'admin1',
            'email' => null,
            'must_change_password' => true,
        ]);

        $admina->forceFill([
            'password' => 'password',
            'email_verified_at' => now(),
        ])->save();

        $adminb = User::factory()->admin()->create([
            'name' => 'Admin 2',
            'student_no' => 'admin2',
            'email' => null,
            'must_change_password' => true,
        ]);

        $adminb->forceFill([
            'password' => 'password',
            'email_verified_at' => now(),
        ])->save();
    }
}
