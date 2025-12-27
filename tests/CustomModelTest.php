<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\CustomEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;

class CustomModelTest extends TestCase
{
    public function test_can_use_custom_event_model(): void
    {
        config(['eventable.model' => CustomEvent::class]);

        $model = TestModel::create(['name' => 'Test']);
        $event = $model->addEvent(TestEvent::Created);

        $this->assertInstanceOf(CustomEvent::class, $event);
    }

    public function test_custom_event_model_has_custom_attributes(): void
    {
        config(['eventable.model' => CustomEvent::class]);

        $model = TestModel::create(['name' => 'Test']);
        $event = $model->addEvent(TestEvent::Created);

        $this->assertEquals('custom_value', $event->custom_attribute);
    }

    public function test_events_relationship_uses_custom_model(): void
    {
        config(['eventable.model' => CustomEvent::class]);

        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);

        $events = $model->events;

        $this->assertCount(2, $events);
        $this->assertInstanceOf(CustomEvent::class, $events->first());
    }

    public function test_custom_table_name(): void
    {
        config(['eventable.table' => 'activity_log']);

        $event = new Event;

        $this->assertEquals('activity_log', $event->getTable());
    }

    public function test_scopes_work_with_custom_model(): void
    {
        config(['eventable.model' => CustomEvent::class]);

        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);

        $models = TestModel::whereEventHasHappened(TestEvent::Created)->get();

        $this->assertCount(1, $models);
    }
}
