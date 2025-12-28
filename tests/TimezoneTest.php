<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('happenedAfter with different timezone', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 10:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow();

    $queryTime = Carbon::parse('2024-06-15 05:00:00', 'America/New_York'); // 09:00 UTC

    $events = Event::happenedAfter($queryTime)->get();

    expect($events)->toHaveCount(1);
});

it('happenedBefore with different timezone', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 10:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow();

    $queryTime = Carbon::parse('2024-06-15 07:00:00', 'America/New_York'); // 11:00 UTC

    $events = Event::happenedBefore($queryTime)->get();

    expect($events)->toHaveCount(1);
});

it('happenedAfter excludes events before threshold', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 08:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow();

    $queryTime = Carbon::parse('2024-06-15 03:00:00', 'America/Los_Angeles');

    $events = Event::happenedAfter($queryTime)->get();

    expect($events)->toHaveCount(1);
});

it('happenedBefore excludes events after threshold', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 08:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 14:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow();

    $queryTime = Carbon::parse('2024-06-15 03:00:00', 'America/Los_Angeles');

    $events = Event::happenedBefore($queryTime)->get();

    expect($events)->toHaveCount(1);
});

it('chained time scopes with timezones', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 06:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 10:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 14:00:00');
    $model->addEvent(TestEvent::Viewed);

    Carbon::setTestNow();

    $after = Carbon::parse('2024-06-15 02:00:00', 'America/New_York');
    $before = Carbon::parse('2024-06-15 08:00:00', 'America/New_York');

    $events = Event::happenedAfter($after)->happenedBefore($before)->get();

    expect($events)->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| happenedToday() with timezone parameter
|--------------------------------------------------------------------------
*/

it('happenedToday with explicit timezone', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 03:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 10:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $eventsUtc = Event::happenedToday()->get();
    expect($eventsUtc)->toHaveCount(2);

    $eventsNy = Event::happenedToday('America/New_York')->get();
    expect($eventsNy)->toHaveCount(1);
});

it('happenedToday timezone boundary', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 04:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 20:00:00');

    $eventsNy = Event::happenedToday('America/New_York')->get();
    expect($eventsNy)->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| happenedThisWeek() with timezone parameter
|--------------------------------------------------------------------------
*/

it('happenedThisWeek with explicit timezone', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-16 14:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-17 02:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-19 12:00:00');

    $eventsUtc = Event::happenedThisWeek()->get();
    expect($eventsUtc)->toHaveCount(1);

    $eventsTokyo = Event::happenedThisWeek('Asia/Tokyo')->get();
    expect($eventsTokyo)->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| happenedThisMonth() with timezone parameter
|--------------------------------------------------------------------------
*/

it('happenedThisMonth with explicit timezone', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-05-31 22:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $eventsUtc = Event::happenedThisMonth()->get();
    expect($eventsUtc)->toHaveCount(1);

    $eventsTokyo = Event::happenedThisMonth('Asia/Tokyo')->get();
    expect($eventsTokyo)->toHaveCount(2);
});
