<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

it('uses configured table name', function () {
    $event = new Event;

    expect($event->getTable())->toBe('events');
});

it('uses custom table name from config', function () {
    config(['eventable.table' => 'custom_events']);

    $event = new Event;

    expect($event->getTable())->toBe('custom_events');
});

it('casts data to json', function () {
    $model = TestModel::create(['name' => 'Test']);
    $event = $model->addEvent(TestEvent::Created, ['key' => 'value']);

    $freshEvent = Event::find($event->id);

    expect($freshEvent->data)->toBeArray();
    expect($freshEvent->data)->toBe(['key' => 'value']);
});

it('filters with ofType scope using enum', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Updated);

    $events = Event::ofType(TestEvent::Updated)->get();

    expect($events)->toHaveCount(2);
});

it('filters with ofType scope using integer', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);

    $events = Event::ofType(TestEvent::Created->value)->get();

    expect($events)->toHaveCount(1);
});

it('filters with ofType scope using array', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Deleted);

    $events = Event::ofType([TestEvent::Created->value, TestEvent::Updated->value])->get();

    expect($events)->toHaveCount(2);
});

it('filters with ofType scope using array of enum values', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Deleted);

    $events = Event::ofType([TestEvent::Created->value, TestEvent::Deleted->value])->get();

    expect($events)->toHaveCount(2);
});

it('filters with whereData scope with empty data', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created, ['key' => 'value']);
    $model->addEvent(TestEvent::Updated);

    $events = Event::whereData([])->get();

    expect($events)->toHaveCount(2);
});

it('filters with whereData scope with scalar', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created, 'simple-value');
    $model->addEvent(TestEvent::Updated, 'other-value');

    $events = Event::whereData('simple-value')->get();

    expect($events)->toHaveCount(1);
});

it('filters with whereData scope with array', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Updated, ['field' => 'name', 'old' => 'Old']);
    $model->addEvent(TestEvent::Updated, ['field' => 'email', 'old' => 'old@test.com']);

    $events = Event::whereData(['field' => 'name'])->get();

    expect($events)->toHaveCount(1);
});

it('filters with whereData scope with nested array', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Updated, ['changes' => ['field' => 'name']]);
    $model->addEvent(TestEvent::Updated, ['changes' => ['field' => 'email']]);

    $events = Event::whereData(['changes' => ['field' => 'name']])->get();

    expect($events)->toHaveCount(1);
});

it('filters with happenedAfter scope', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-15 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-20 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-01-25 12:00:00');
    $model->addEvent(TestEvent::Viewed);

    $events = Event::happenedAfter(Carbon::parse('2024-01-18'))->get();

    expect($events)->toHaveCount(2);

    Carbon::setTestNow();
});

it('filters with happenedBefore scope', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-15 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-20 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-01-25 12:00:00');
    $model->addEvent(TestEvent::Viewed);

    $events = Event::happenedBefore(Carbon::parse('2024-01-22'))->get();

    expect($events)->toHaveCount(2);

    Carbon::setTestNow();
});

it('resolves eventable relationship', function () {
    $model = TestModel::create(['name' => 'Test']);
    $event = $model->addEvent(TestEvent::Created);

    $freshEvent = Event::find($event->id);

    expect($freshEvent->eventable)->toBeInstanceOf(TestModel::class);
    expect($freshEvent->eventable->id)->toBe($model->id);
});

it('chains scopes', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-15 12:00:00');
    $model->addEvent(TestEvent::Updated, ['field' => 'name']);

    Carbon::setTestNow('2024-01-25 12:00:00');
    $model->addEvent(TestEvent::Updated, ['field' => 'email']);

    $events = Event::ofType(TestEvent::Updated)
        ->whereData(['field' => 'name'])
        ->happenedBefore(Carbon::parse('2024-01-20'))
        ->get();

    expect($events)->toHaveCount(1);

    Carbon::setTestNow();
});
