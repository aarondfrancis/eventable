<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;

class EventableTest extends TestCase
{
    public function test_event_model_uses_configured_table(): void
    {
        $event = new Event();

        $this->assertEquals(
            config('eventable.table', 'events'),
            $event->getTable()
        );
    }
}
