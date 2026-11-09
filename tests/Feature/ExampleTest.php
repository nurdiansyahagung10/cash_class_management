<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application shows guests the public dashboard', function () {
    $this->get('/')->assertOk()->assertSee('Kas Kelas 1A');
});

test('the login page renders', function () {
    $this->get('/login')->assertOk()->assertSee('No. absen / NIM');
});
