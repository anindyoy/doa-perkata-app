<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Closure yang diberikan ke fungsi test selalu terikat ke class PHPUnit
| tertentu. Defaultnya "PHPUnit\Framework\TestCase". Gunakan fungsi
| "pest()" untuk mengikat class atau trait yang berbeda.
|
| RefreshDatabase TIDAK dipasang global agar file yang mengelola database
| sendiri (mis. ProjectSourceCoverageTest) tidak kena transaksi ganda.
| Tambahkan `uses(RefreshDatabase::class)` di tiap file Feature yang butuh DB.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature');

pest()->tia()->defaultBranch('main');
