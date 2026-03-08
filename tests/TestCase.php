<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\EventableServiceProvider;
use AaronFrancis\Eventable\EventTypeRegistry;
use AaronFrancis\Eventable\Tests\Fixtures\CollidingEvent;
use AaronFrancis\Eventable\Tests\Fixtures\CombinedPruneEvent;
use AaronFrancis\Eventable\Tests\Fixtures\CustomEvent;
use AaronFrancis\Eventable\Tests\Fixtures\PruneableTestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\StringEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerEventTypes();
    }

    protected function tearDown(): void
    {
        EventTypeRegistry::clear();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Staudenmeir\LaravelCte\DatabaseServiceProvider::class,
            EventableServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $driver = env('DB_CONNECTION', 'sqlite');

        Schema::defaultMorphKeyType('int');

        $app['config']->set('database.default', 'testing');

        if ($driver === 'sqlite') {
            $app['config']->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
        } elseif ($driver === 'pgsql') {
            $app['config']->set('database.connections.testing', [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'),
                'database' => env('DB_DATABASE', 'testing'),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', 'postgres'),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
            ]);
        } elseif ($driver === 'mysql') {
            $app['config']->set('database.connections.testing', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'testing'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', 'password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]);
        }
    }

    protected function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh', [
            '--path' => __DIR__.'/database/migrations',
            '--realpath' => true,
        ]);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }

    protected function registerEventTypes(): void
    {
        EventTypeRegistry::register('test', TestEvent::class);
        EventTypeRegistry::register('string', StringEvent::class);
        EventTypeRegistry::register('colliding', CollidingEvent::class);
        EventTypeRegistry::register('pruneable', PruneableTestEvent::class);
        EventTypeRegistry::register('combined', CombinedPruneEvent::class);
        EventTypeRegistry::register('custom', CustomEvent::class);
    }
}
