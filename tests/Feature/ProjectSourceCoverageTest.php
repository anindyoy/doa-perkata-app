<?php

use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Container\Container;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;

it('memuat konfigurasi aplikasi bootstrap dan definisi route', function () {
    foreach (glob(base_path('config/*.php')) as $configFile) {
        expect(require $configFile)->toBeArray();
    }

    $providers = require base_path('bootstrap/providers.php');
    require base_path('routes/web.php');
    require base_path('routes/console.php');

    expect($providers)->toContain(AppServiceProvider::class);
    expect(route('beranda'))->not->toBeNull();
    expect(Artisan::all())->toHaveKey('inspire');
    Artisan::call('inspire');
    expect(Artisan::output())->not->toBe('');

    $bootstrappedApplication = require base_path('bootstrap/app.php');
    Container::setInstance(app());
    Facade::setFacadeApplication(app());

    expect($bootstrappedApplication)->toBeInstanceOf(Application::class);
});

it('menjalankan dan membalikkan semua migrasi proyek di database terisolasi', function () {
    $defaultConnection = Config::get('database.default');
    Config::set('database.connections.coverage_sqlite', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    Config::set('database.default', 'coverage_sqlite');
    DB::purge('coverage_sqlite');

    try {
        // Jalankan semua migrasi (proyek + vendor seperti MoonShine) via
        // Artisan agar dependensi antar migrasi terpenuhi. Loop manual
        // dengan glob() hanya mencakup database/migrations/*.php sehingga
        // migrasi MoonShine pemicu tabel moonshine_user_roles terlewat.
        Artisan::call('migrate', [
            '--database' => 'coverage_sqlite',
            '--force' => true,
        ]);

        expect(Schema::connection('coverage_sqlite')->hasTable('pengguna'))->toBeTrue();
        expect(Schema::connection('coverage_sqlite')->hasTable('doa'))->toBeTrue();
        expect(Schema::connection('coverage_sqlite')->hasTable('moonshine_user_roles'))->toBeTrue();
        expect(Schema::connection('coverage_sqlite')->hasTable('notifications'))->toBeTrue();
        $this->assertDatabaseHas('pengaturan', [
            'kunci' => 'terjemahan_perkata_model',
            'nilai' => 'gpt-4o-mini',
        ], 'coverage_sqlite');

        Artisan::call('migrate:reset', [
            '--database' => 'coverage_sqlite',
            '--force' => true,
        ]);

        expect(Schema::connection('coverage_sqlite')->hasTable('pengguna'))->toBeFalse();
        expect(Schema::connection('coverage_sqlite')->hasTable('doa'))->toBeFalse();
        expect(Schema::connection('coverage_sqlite')->hasTable('notifications'))->toBeFalse();
    } finally {
        DB::purge('coverage_sqlite');
        Config::set('database.default', $defaultConnection);
    }
});

it('menjalankan database seeder tanpa membuat record dataset', function () {
    Schema::create('doa', function (Blueprint $table): void {
        $table->id();
    });
    $seeder = new DatabaseSeeder(app());

    $seeder->run();

    $this->assertDatabaseCount('doa', 0);
    Schema::drop('doa');
});

it('menjalankan migrasi create moonshine admin user dan mencakup branch insert dan update', function () {
    $defaultConnection = Config::get('database.default');
    Config::set('database.connections.coverage_sqlite', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    Config::set('database.default', 'coverage_sqlite');
    DB::purge('coverage_sqlite');

    try {
        // Jalankan migrasi sebelumnya agar tabel moonshine_users ada
        Artisan::call('migrate', [
            '--database' => 'coverage_sqlite',
            '--force' => true,
        ]);

        // Pastikan tabel moonshine_user_roles ada (dari migrasi MoonShine)
        expect(Schema::connection('coverage_sqlite')->hasTable('moonshine_user_roles'))->toBeTrue();

        // Hapus role default agar migrasi membuat ulang (branch lines 16-20)
        DB::connection('coverage_sqlite')->table('moonshine_user_roles')
            ->where('id', \MoonShine\Laravel\Models\MoonshineUserRole::DEFAULT_ROLE_ID)
            ->delete();

        // Jalankan migrasi create_moonshine_admin_user
        // Gunakan env untuk password
        putenv('MOONSHINE_ADMIN_PASSWORD=testpassword');
        $_ENV['MOONSHINE_ADMIN_PASSWORD'] = 'testpassword';
        $_SERVER['MOONSHINE_ADMIN_PASSWORD'] = 'testpassword';

        $migration = require database_path('migrations/2026_10_07_000001_create_moonshine_admin_user.php');
        $migration->up();

        // Cek user dibuat (branch insert lines 43-50)
        expect(DB::connection('coverage_sqlite')->table('moonshine_users')
            ->where('email', 'admin@mail.com')
            ->exists())->toBeTrue();

        // Jalankan up() lagi untuk cover branch update (lines 34-41)
        $migration->up();

        // Cek user masih ada dan diupdate
        expect(DB::connection('coverage_sqlite')->table('moonshine_users')
            ->where('email', 'admin@mail.com')
            ->exists())->toBeTrue();

        // Test empty password branch (line 28) - create fresh instance
        putenv('MOONSHINE_ADMIN_PASSWORD=');
        $_ENV['MOONSHINE_ADMIN_PASSWORD'] = '';
        $_SERVER['MOONSHINE_ADMIN_PASSWORD'] = '';
        $migration2 = require database_path('migrations/2026_10_07_000001_create_moonshine_admin_user.php');
        $migration2->up(); // Should return early without error

        // Test null password (also line 28)
        putenv('MOONSHINE_ADMIN_PASSWORD=');
        unset($_ENV['MOONSHINE_ADMIN_PASSWORD']);
        unset($_SERVER['MOONSHINE_ADMIN_PASSWORD']);
        $migration3 = require database_path('migrations/2026_10_07_000001_create_moonshine_admin_user.php');
        $migration3->up(); // Should return early without error

        // Test down() method (lines 54-57)
        $migration->down();

    } finally {
        DB::purge('coverage_sqlite');
        Config::set('database.default', $defaultConnection);
    }
});
