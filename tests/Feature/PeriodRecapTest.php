<?php

use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can view period payment recap and filter by status', function () {
    $admin = User::factory()->admin()->create();
    $student1 = User::factory()->create(['name' => 'Adit Pratama', 'student_no' => '01']);
    $student2 = User::factory()->create(['name' => 'Bela Safitri', 'student_no' => '02']);

    $period = BillingPeriod::factory()->create(['title' => 'Kas Pekan 12', 'amount' => 10000]);

    Invoice::factory()->paid()->create([
        'billing_period_id' => $period->id,
        'user_id' => $student1->id,
        'amount' => 10000,
    ]);

    Invoice::factory()->create([
        'billing_period_id' => $period->id,
        'user_id' => $student2->id,
        'amount' => 10000,
        'status' => 'unpaid',
    ]);

    // View all
    $this->actingAs($admin)
        ->get(route('periods.show', $period))
        ->assertOk()
        ->assertSee('Kas Pekan 12')
        ->assertSee('Adit Pratama')
        ->assertSee('Bela Safitri')
        ->assertSee('Ingatkan WA');

    // Filter unpaid only
    $this->actingAs($admin)
        ->get(route('periods.show', [$period, 'status' => 'unpaid']))
        ->assertOk()
        ->assertSee('Bela Safitri')
        ->assertDontSee('Adit Pratama');

    // Filter paid only
    $this->actingAs($admin)
        ->get(route('periods.show', [$period, 'status' => 'paid']))
        ->assertOk()
        ->assertSee('Adit Pratama')
        ->assertDontSee('Bela Safitri');
});

test('regular user cannot view period recap page', function () {
    $user = User::factory()->create();
    $period = BillingPeriod::factory()->create();

    $this->actingAs($user)
        ->get(route('periods.show', $period))
        ->assertForbidden();
});

test('period recap targets specific student phone number in WhatsApp reminder if available', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create([
        'name' => 'Citra Lestari',
        'student_no' => '03',
        'phone' => '081234567890',
    ]);

    $period = BillingPeriod::factory()->create(['title' => 'Kas Pekan 13', 'amount' => 15000]);

    Invoice::factory()->create([
        'billing_period_id' => $period->id,
        'user_id' => $student->id,
        'amount' => 15000,
        'status' => 'unpaid',
    ]);

    $this->actingAs($admin)
        ->get(route('periods.show', $period))
        ->assertOk()
        ->assertSee('https://wa.me/6281234567890?text=');
});
