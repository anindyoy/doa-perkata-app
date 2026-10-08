<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
it('mengembalikan respons sukses untuk halaman utama', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
