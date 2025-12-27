<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\PruneableTestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

class EdgeCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_empty_database_returns_empty_collection(): void
    {
        $events = Event::all();

        $this->assertCount(0, $events);
    }

    public function test_where_event_has_happened_on_empty_database(): void
    {
        $models = TestModel::whereEventHasHappened(TestEvent::Created)->get();

        $this->assertCount(0, $models);
    }

    public function test_where_event_hasnt_happened_on_empty_models(): void
    {
        // No models created
        $models = TestModel::whereEventHasntHappened(TestEvent::Created)->get();

        $this->assertCount(0, $models);
    }

    public function test_where_event_hasnt_happened_with_no_events(): void
    {
        // Models exist but no events
        TestModel::create(['name' => 'Model 1']);
        TestModel::create(['name' => 'Model 2']);

        $models = TestModel::whereEventHasntHappened(TestEvent::Created)->get();

        $this->assertCount(2, $models);
    }

    public function test_prune_on_empty_database(): void
    {
        config(['eventable.event_enum' => PruneableTestEvent::class]);

        $this->artisan('eventable:prune')
            ->expectsOutputToContain('0 records pruned')
            ->assertExitCode(0);
    }

    public function test_add_event_with_complex_nested_data(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $complexData = [
            'user' => [
                'id' => 123,
                'profile' => [
                    'name' => 'John Doe',
                    'settings' => [
                        'notifications' => true,
                        'theme' => 'dark',
                    ],
                ],
            ],
            'metadata' => [
                'ip' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
            ],
        ];

        $event = $model->addEvent(TestEvent::Updated, $complexData);

        $freshEvent = Event::find($event->id);

        $this->assertEquals($complexData, $freshEvent->data);
        $this->assertEquals('John Doe', $freshEvent->data['user']['profile']['name']);
    }

    public function test_where_data_with_deeply_nested_query(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $model->addEvent(TestEvent::Updated, [
            'changes' => [
                'field' => [
                    'old' => 'value1',
                    'new' => 'value2',
                ],
            ],
        ]);

        $model->addEvent(TestEvent::Updated, [
            'changes' => [
                'field' => [
                    'old' => 'other',
                    'new' => 'different',
                ],
            ],
        ]);

        $events = Event::whereData([
            'changes' => [
                'field' => [
                    'old' => 'value1',
                ],
            ],
        ])->get();

        $this->assertCount(1, $events);
    }

    public function test_multiple_events_same_second(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-15 12:00:00');

        // Add multiple events at exactly the same time
        $event1 = $model->addEvent(TestEvent::Created);
        $event2 = $model->addEvent(TestEvent::Updated);
        $event3 = $model->addEvent(TestEvent::Viewed);

        $this->assertEquals(
            $event1->created_at->toDateTimeString(),
            $event2->created_at->toDateTimeString()
        );

        $this->assertCount(3, $model->events);
    }

    public function test_add_event_with_null_in_data_array(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(TestEvent::Updated, [
            'old_value' => null,
            'new_value' => 'something',
        ]);

        $freshEvent = Event::find($event->id);

        $this->assertNull($freshEvent->data['old_value']);
        $this->assertEquals('something', $freshEvent->data['new_value']);
    }

    public function test_add_event_with_boolean_data(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(TestEvent::Updated, [
            'active' => true,
            'archived' => false,
        ]);

        $freshEvent = Event::find($event->id);

        $this->assertTrue($freshEvent->data['active']);
        $this->assertFalse($freshEvent->data['archived']);
    }

    public function test_add_event_with_numeric_data(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(TestEvent::Updated, [
            'count' => 42,
            'price' => 19.99,
            'negative' => -5,
        ]);

        $freshEvent = Event::find($event->id);

        $this->assertEquals(42, $freshEvent->data['count']);
        $this->assertEquals(19.99, $freshEvent->data['price']);
        $this->assertEquals(-5, $freshEvent->data['negative']);
    }

    public function test_add_event_with_empty_array_data(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(TestEvent::Updated, []);

        $freshEvent = Event::find($event->id);

        $this->assertEquals([], $freshEvent->data);
    }

    public function test_scope_chaining_with_empty_results(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-15 12:00:00');
        $model->addEvent(TestEvent::Created, ['key' => 'value']);

        // Query that should return nothing due to conflicting conditions
        $events = Event::ofType(TestEvent::Updated)
            ->whereData(['key' => 'value'])
            ->get();

        $this->assertCount(0, $events);
    }

    public function test_model_with_many_events(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Create 100 events
        for ($i = 0; $i < 100; $i++) {
            $model->addEvent(TestEvent::Viewed, ['view_count' => $i]);
        }

        $this->assertCount(100, $model->events);
        $this->assertEquals(100, Event::count());
    }
}
