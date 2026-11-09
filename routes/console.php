<?php

use App\Models\PendingMember;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ledger:verify')->dailyAt('02:00');
Schedule::command('ledger:anchor')->dailyAt('23:55');
Schedule::command('model:prune', ['--model' => [PendingMember::class]])->daily();
Schedule::command('notion:sync')->hourly()->withoutOverlapping(60);
