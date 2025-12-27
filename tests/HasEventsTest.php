<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;

class HasEventsTest extends TestCase
{
    public function test_can_add_event_to_model(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(TestEvent::Created);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals(TestEvent::Created->value, $event->type);
        $this->assertEquals($model->id, $event->eventable_id);
        $this->assertEquals(TestModel::class, $event->eventable_type);
    }

    public function test_can_add_event_with_data(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(TestEvent::Updated, ['field' => 'name', 'old' => 'Old', 'new' => 'New']);

        $this->assertEquals(['field' => 'name', 'old' => 'Old', 'new' => 'New'], $event->data);
    }

    public function test_can_add_event_with_null_data(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(TestEvent::Viewed);

        $this->assertNull($event->data);
    }

    public function test_events_relationship_returns_all_events(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Viewed);

        $this->assertCount(3, $model->events);
    }

    public function test_events_are_scoped_to_model(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        $model1->addEvent(TestEvent::Created);
        $model1->addEvent(TestEvent::Updated);
        $model2->addEvent(TestEvent::Created);

        $this->assertCount(2, $model1->events);
        $this->assertCount(1, $model2->events);
    }

    public function test_where_event_has_happened_scope(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        $model1->addEvent(TestEvent::Exported);
        $model2->addEvent(TestEvent::Viewed);

        $exported = TestModel::whereEventHasHappened(TestEvent::Exported)->get();

        $this->assertCount(1, $exported);
        $this->assertEquals($model1->id, $exported->first()->id);
    }

    public function test_where_event_hasnt_happened_scope(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        $model1->addEvent(TestEvent::Exported);

        $notExported = TestModel::whereEventHasntHappened(TestEvent::Exported)->get();

        $this->assertCount(2, $notExported);
        $this->assertFalse($notExported->contains('id', $model1->id));
    }

    public function test_where_event_has_happened_with_data(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        $model1->addEvent(TestEvent::Updated, ['field' => 'name']);
        $model2->addEvent(TestEvent::Updated, ['field' => 'email']);

        $nameUpdated = TestModel::whereEventHasHappened(TestEvent::Updated, ['field' => 'name'])->get();

        $this->assertCount(1, $nameUpdated);
        $this->assertEquals($model1->id, $nameUpdated->first()->id);
    }

    public function test_where_event_hasnt_happened_with_data(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        $model1->addEvent(TestEvent::Updated, ['field' => 'name']);
        $model2->addEvent(TestEvent::Updated, ['field' => 'email']);

        $notNameUpdated = TestModel::whereEventHasntHappened(TestEvent::Updated, ['field' => 'name'])->get();

        $this->assertCount(2, $notNameUpdated);
        $this->assertFalse($notNameUpdated->contains('id', $model1->id));
    }
}
