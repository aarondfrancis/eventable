<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('empty database returns empty collection', function () {
    $events = Event::all();

    expect($events)->toHaveCount(0);
});

it('whereEventHasHappened on empty database', function () {
    $models = TestModel::whereEventHasHappened(TestEvent::Created)->get();

    expect($models)->toHaveCount(0);
});

it('whereEventHasntHappened on empty models', function () {
    $models = TestModel::whereEventHasntHappened(TestEvent::Created)->get();

    expect($models)->toHaveCount(0);
});

it('whereEventHasntHappened with no events', function () {
    TestModel::create(['name' => 'Model 1']);
    TestModel::create(['name' => 'Model 2']);

    $models = TestModel::whereEventHasntHappened(TestEvent::Created)->get();

    expect($models)->toHaveCount(2);
});

it('prune on empty database', function () {
    $this->artisan('eventable:prune')
        ->expectsOutputToContain('0 records pruned')
        ->assertExitCode(0);
});

it('adds event with complex nested data', function () {
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

    expect($freshEvent->data)->toBe($complexData);
    expect($freshEvent->data['user']['profile']['name'])->toBe('John Doe');
});

it('whereData with deeply nested query', function () {
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

    expect($events)->toHaveCount(1);
});

it('handles multiple events same second', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-15 12:00:00');

    $event1 = $model->addEvent(TestEvent::Created);
    $event2 = $model->addEvent(TestEvent::Updated);
    $event3 = $model->addEvent(TestEvent::Viewed);

    expect($event1->created_at->toDateTimeString())->toBe($event2->created_at->toDateTimeString());

    expect($model->events)->toHaveCount(3);
});

it('adds event with null in data array', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(TestEvent::Updated, [
        'old_value' => null,
        'new_value' => 'something',
    ]);

    $freshEvent = Event::find($event->id);

    expect($freshEvent->data['old_value'])->toBeNull();
    expect($freshEvent->data['new_value'])->toBe('something');
});

it('adds event with boolean data', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(TestEvent::Updated, [
        'active' => true,
        'archived' => false,
    ]);

    $freshEvent = Event::find($event->id);

    expect($freshEvent->data['active'])->toBeTrue();
    expect($freshEvent->data['archived'])->toBeFalse();
});

it('adds event with numeric data', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(TestEvent::Updated, [
        'count' => 42,
        'price' => 19.99,
        'negative' => -5,
    ]);

    $freshEvent = Event::find($event->id);

    expect($freshEvent->data['count'])->toBe(42);
    expect($freshEvent->data['price'])->toBe(19.99);
    expect($freshEvent->data['negative'])->toBe(-5);
});

it('adds event with empty array data', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(TestEvent::Updated, []);

    $freshEvent = Event::find($event->id);

    expect($freshEvent->data)->toBe([]);
});

it('scope chaining with empty results', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-15 12:00:00');
    $model->addEvent(TestEvent::Created, ['key' => 'value']);

    $events = Event::ofType(TestEvent::Updated)
        ->whereData(['key' => 'value'])
        ->get();

    expect($events)->toHaveCount(0);
});

it('model with many events', function () {
    $model = TestModel::create(['name' => 'Test']);

    for ($i = 0; $i < 100; $i++) {
        $model->addEvent(TestEvent::Viewed, ['view_count' => $i]);
    }

    expect($model->events)->toHaveCount(100);
    expect(Event::count())->toBe(100);
});
