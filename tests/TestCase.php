<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\EventableServiceProvider;
use AaronFrancis\Eventable\EventTypeRegistry;
use AaronFrancis\Eventable\Tests\Fixtures\CombinedPruneEvent;
use AaronFrancis\Eventable\Tests\Fixtures\CustomEvent;
use AaronFrancis\Eventable\Tests\Fixtures\PruneableTestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\StringEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
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
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('type_class');
            $table->string('type');
            $table->unsignedBigInteger('eventable_id');
            $table->string('eventable_type');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['eventable_id', 'eventable_type']);
            $table->index(['eventable_type', 'type_class', 'type']);
            $table->index(['type_class', 'type', 'created_at']);
        });

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('another_models', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    protected function registerEventTypes(): void
    {
        EventTypeRegistry::register('test', TestEvent::class);
        EventTypeRegistry::register('string', StringEvent::class);
        EventTypeRegistry::register('pruneable', PruneableTestEvent::class);
        EventTypeRegistry::register('combined', CombinedPruneEvent::class);
        EventTypeRegistry::register('custom', CustomEvent::class);
    }
}
