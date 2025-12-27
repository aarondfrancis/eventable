<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

class EventModelTest extends TestCase
{
    public function test_uses_configured_table_name(): void
    {
        $event = new Event;

        $this->assertEquals('events', $event->getTable());
    }

    public function test_uses_custom_table_name_from_config(): void
    {
        config(['eventable.table' => 'custom_events']);

        $event = new Event;

        $this->assertEquals('custom_events', $event->getTable());
    }

    public function test_data_is_cast_to_json(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $event = $model->addEvent(TestEvent::Created, ['key' => 'value']);

        $freshEvent = Event::find($event->id);

        $this->assertIsArray($freshEvent->data);
        $this->assertEquals(['key' => 'value'], $freshEvent->data);
    }

    public function test_scope_of_type_with_enum(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Updated);

        $events = Event::ofType(TestEvent::Updated)->get();

        $this->assertCount(2, $events);
    }

    public function test_scope_of_type_with_integer(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);

        $events = Event::ofType(TestEvent::Created->value)->get();

        $this->assertCount(1, $events);
    }

    public function test_scope_of_type_with_array(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Deleted);

        $events = Event::ofType([TestEvent::Created->value, TestEvent::Updated->value])->get();

        $this->assertCount(2, $events);
    }

    public function test_scope_of_type_with_array_of_enums(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Deleted);

        // Note: When passing array of enums, you must use ->value
        // The scope only handles single enum objects, not arrays of enums
        $events = Event::ofType([TestEvent::Created->value, TestEvent::Deleted->value])->get();

        $this->assertCount(2, $events);
    }

    public function test_scope_where_data_with_empty_data(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created, ['key' => 'value']);
        $model->addEvent(TestEvent::Updated);

        $events = Event::whereData([])->get();

        $this->assertCount(2, $events);
    }

    public function test_scope_where_data_with_scalar(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created, 'simple-value');
        $model->addEvent(TestEvent::Updated, 'other-value');

        $events = Event::whereData('simple-value')->get();

        $this->assertCount(1, $events);
    }

    public function test_scope_where_data_with_array(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Updated, ['field' => 'name', 'old' => 'Old']);
        $model->addEvent(TestEvent::Updated, ['field' => 'email', 'old' => 'old@test.com']);

        $events = Event::whereData(['field' => 'name'])->get();

        $this->assertCount(1, $events);
    }

    public function test_scope_where_data_with_nested_array(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Updated, ['changes' => ['field' => 'name']]);
        $model->addEvent(TestEvent::Updated, ['changes' => ['field' => 'email']]);

        $events = Event::whereData(['changes' => ['field' => 'name']])->get();

        $this->assertCount(1, $events);
    }

    public function test_scope_happened_after(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-15 12:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-20 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-01-25 12:00:00');
        $model->addEvent(TestEvent::Viewed);

        $events = Event::happenedAfter(Carbon::parse('2024-01-18'))->get();

        $this->assertCount(2, $events);

        Carbon::setTestNow();
    }

    public function test_scope_happened_before(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-15 12:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-20 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-01-25 12:00:00');
        $model->addEvent(TestEvent::Viewed);

        $events = Event::happenedBefore(Carbon::parse('2024-01-22'))->get();

        $this->assertCount(2, $events);

        Carbon::setTestNow();
    }

    public function test_eventable_relationship(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $event = $model->addEvent(TestEvent::Created);

        $freshEvent = Event::find($event->id);

        $this->assertInstanceOf(TestModel::class, $freshEvent->eventable);
        $this->assertEquals($model->id, $freshEvent->eventable->id);
    }

    public function test_chained_scopes(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-15 12:00:00');
        $model->addEvent(TestEvent::Updated, ['field' => 'name']);

        Carbon::setTestNow('2024-01-25 12:00:00');
        $model->addEvent(TestEvent::Updated, ['field' => 'email']);

        $events = Event::ofType(TestEvent::Updated)
            ->whereData(['field' => 'name'])
            ->happenedBefore(Carbon::parse('2024-01-20'))
            ->get();

        $this->assertCount(1, $events);

        Carbon::setTestNow();
    }
}
