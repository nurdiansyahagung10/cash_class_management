<?php

use App\Livewire\ExpenseAssistant;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.finance_assistant.base_url' => 'http://9router.test/v1/',
        'services.finance_assistant.api_key' => 'test-key',
        'services.finance_assistant.model' => 'test-model',
    ]);
});

function fakeAssistantReply(string $answer = 'Saldo masih aman untuk pengeluaran itu.'): void
{
    Http::fake([
        '9router.test/*' => Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => $answer]]]]),
    ]);
}

test('staff can ask the finance assistant with class cash data as context', function () {
    $staff = User::factory()->staff()->create();
    User::factory()->create(['name' => 'Siswa Rahasia']);
    Expense::create(['title' => 'Spidol papan tulis', 'amount' => 24000, 'spent_at' => now(), 'notes' => 'catatan internal', 'created_by' => $staff->id]);
    fakeAssistantReply('Masih **aman**.');

    Livewire::actingAs($staff)
        ->test(ExpenseAssistant::class)
        ->set('question', 'Cukup nggak buat beli spidol lagi?')
        ->call('ask')
        ->assertHasNoErrors()
        ->assertSet('question', '')
        ->assertCount('messages', 2)
        ->assertSet('messages.0.role', 'user')
        ->assertSet('messages.0.content', 'Cukup nggak buat beli spidol lagi?')
        ->assertSet('messages.1.role', 'assistant')
        ->assertSet('messages.1.content', 'Masih **aman**.')
        ->assertSet('messages.1.at', fn (string $time) => preg_match('/^\d{2}\.\d{2}$/', $time) === 1)
        ->assertSeeHtml('<strong>aman</strong>');

    Http::assertSent(function (Request $request) {
        $system = $request['messages'][0]['content'];

        return $request->url() === 'http://9router.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'test-model'
            && $request['messages'][0]['role'] === 'system'
            && str_contains($system, 'asisten keuangan')
            && str_contains($system, 'Spidol papan tulis Rp24.000')
            && ! str_contains($system, 'Siswa Rahasia')
            && ! str_contains($system, 'catatan internal')
            && end($request->data()['messages'])['content'] === 'Cukup nggak buat beli spidol lagi?';
    });
});

test('a suggested question is sent as-is and follow-ups carry the earlier turns', function () {
    $staff = User::factory()->staff()->create();
    fakeAssistantReply();

    Livewire::actingAs($staff)
        ->test(ExpenseAssistant::class)
        ->assertSee(ExpenseAssistant::SUGGESTIONS[0])
        ->call('askSuggestion', 0)
        ->call('send', 'Kalau dua kali lipat?')
        ->assertCount('messages', 4)
        ->assertDontSee(ExpenseAssistant::SUGGESTIONS[1]);

    $requests = Http::recorded();
    expect($requests)->toHaveCount(2)
        ->and($requests[0][0]['messages'][1]['content'])->toBe(ExpenseAssistant::SUGGESTIONS[0])
        ->and(array_column($requests[1][0]['messages'], 'role'))->toBe(['system', 'user', 'assistant', 'user']);
});

test('the assistant explains instead of failing when it is unconfigured or the AI server errors', function () {
    $staff = User::factory()->staff()->create();

    Http::fake(['9router.test/*' => Http::response('upstream down', 502)]);
    config(['services.finance_assistant.api_key' => null]);

    Livewire::actingAs($staff)
        ->test(ExpenseAssistant::class)
        ->assertSee('Asisten belum aktif')
        ->set('question', 'Halo?')
        ->call('ask')
        ->assertSet('messages.1.error', true)
        ->assertSee('belum dikonfigurasi');
    Http::assertNothingSent();

    config(['services.finance_assistant.api_key' => 'test-key']);

    Livewire::actingAs($staff)
        ->test(ExpenseAssistant::class)
        ->set('question', 'Halo?')
        ->call('ask')
        ->assertSet('messages.1.error', true)
        ->assertSee('kode 502');
});

test('questions are validated and rate limited per user', function () {
    $staff = User::factory()->staff()->create();
    fakeAssistantReply();

    Livewire::actingAs($staff)
        ->test(ExpenseAssistant::class)
        ->set('question', '   ')
        ->call('ask')
        ->assertHasErrors(['question' => 'required'])
        ->set('question', str_repeat('a', 501))
        ->call('ask')
        ->assertHasErrors(['question' => 'max']);

    foreach (range(1, ExpenseAssistant::HOURLY_LIMIT) as $attempt) {
        RateLimiter::hit('finance-assistant:'.$staff->id, 3600);
    }

    Livewire::actingAs($staff)
        ->test(ExpenseAssistant::class)
        ->set('question', 'Satu lagi?')
        ->call('ask')
        ->assertHasErrors('question')
        ->assertSet('messages', []);
    Http::assertNothingSent();
});

test('only staff and admins get the assistant', function () {
    $member = User::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('expenses.index'))->assertOk()->assertSeeLivewire(ExpenseAssistant::class);
    $this->actingAs($member)->get(route('expenses.index'))->assertOk()->assertDontSeeLivewire(ExpenseAssistant::class);

    Livewire::actingAs($member)->test(ExpenseAssistant::class)->assertForbidden();
});
