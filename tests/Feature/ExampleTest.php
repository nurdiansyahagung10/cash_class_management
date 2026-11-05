<?php

test('the application redirects guests to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

test('the login page renders', function () {
    $this->get('/login')->assertOk()->assertSee('No. absen / NIM');
});
