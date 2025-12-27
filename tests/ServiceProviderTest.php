<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use Illuminate\Database\Eloquent\Relations\Relation;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertNotNull(config('eventable'));
        $this->assertEquals('events', config('eventable.table'));
    }

    public function test_morph_map_is_registered(): void
    {
        $morphMap = Relation::morphMap();

        $this->assertArrayHasKey('event', $morphMap);
        $this->assertEquals(Event::class, $morphMap['event']);
    }

    public function test_morph_map_can_be_disabled(): void
    {
        // Reset morph map
        Relation::morphMap([], false);

        config(['eventable.register_morph_map' => false]);

        // Re-boot the service provider
        $this->app->register(\AaronFrancis\Eventable\EventableServiceProvider::class, true);

        $morphMap = Relation::morphMap();

        $this->assertArrayNotHasKey('event', $morphMap);
    }

    public function test_custom_morph_alias(): void
    {
        // Reset morph map
        Relation::morphMap([], false);

        config(['eventable.morph_alias' => 'custom_event']);

        // Re-boot the service provider
        $this->app->register(\AaronFrancis\Eventable\EventableServiceProvider::class, true);

        $morphMap = Relation::morphMap();

        $this->assertArrayHasKey('custom_event', $morphMap);
        $this->assertEquals(Event::class, $morphMap['custom_event']);
    }

    public function test_custom_event_model_in_morph_map(): void
    {
        // Reset morph map
        Relation::morphMap([], false);

        config(['eventable.model' => \AaronFrancis\Eventable\Tests\Fixtures\TestModel::class]);

        // Re-boot the service provider
        $this->app->register(\AaronFrancis\Eventable\EventableServiceProvider::class, true);

        $morphMap = Relation::morphMap();

        $this->assertEquals(\AaronFrancis\Eventable\Tests\Fixtures\TestModel::class, $morphMap['event']);
    }

    public function test_command_is_registered(): void
    {
        $this->assertTrue(
            collect($this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all())
                ->has('eventable:prune')
        );
    }
}
