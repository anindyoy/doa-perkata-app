<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class ProjectSourceCoverageTest extends TestCase
{
    public function test_it_loads_application_configuration_bootstrap_and_route_definitions(): void
    {
        foreach (glob(base_path('config/*.php')) as $configFile) {
            $this->assertIsArray(require $configFile);
        }

        $providers = require base_path('bootstrap/providers.php');
        require base_path('routes/web.php');
        require base_path('routes/console.php');

        $this->assertContains(AppServiceProvider::class, $providers);
        $this->assertTrue(route('beranda') !== null);
        $this->assertArrayHasKey('inspire', Artisan::all());
        Artisan::call('inspire');
        $this->assertNotSame('', Artisan::output());

        $bootstrappedApplication = require base_path('bootstrap/app.php');
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        $this->assertInstanceOf(Application::class, $bootstrappedApplication);
    }

    public function test_it_runs_and_reverses_all_project_migrations_on_an_isolated_database(): void
    {
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
            $migrationFiles = glob(base_path('database/migrations/*.php'));

            foreach ($migrationFiles as $migrationFile) {
                $migration = require $migrationFile;
                $migration->up();
            }

            $this->assertTrue(Schema::connection('coverage_sqlite')->hasTable('pengguna'));
            $this->assertTrue(Schema::connection('coverage_sqlite')->hasTable('doa'));
            $this->assertTrue(Schema::connection('coverage_sqlite')->hasTable('notifications'));
            $this->assertDatabaseHas('pengaturan', [
                'kunci' => 'terjemahan_perkata_model',
                'nilai' => 'gpt-4o-mini',
            ], 'coverage_sqlite');

            foreach (array_reverse($migrationFiles) as $migrationFile) {
                $migration = require $migrationFile;
                $migration->down();
            }

            $this->assertFalse(Schema::connection('coverage_sqlite')->hasTable('pengguna'));
            $this->assertFalse(Schema::connection('coverage_sqlite')->hasTable('doa'));
            $this->assertFalse(Schema::connection('coverage_sqlite')->hasTable('notifications'));
        } finally {
            DB::purge('coverage_sqlite');
            Config::set('database.default', $defaultConnection);
        }
    }

    public function test_database_seeder_completes_without_creating_dataset_records(): void
    {
        Schema::create('doa', function (Blueprint $table): void {
            $table->id();
        });
        $seeder = new DatabaseSeeder($this->app);

        $seeder->run();

        $this->assertDatabaseCount('doa', 0);
        Schema::drop('doa');
    }
}
