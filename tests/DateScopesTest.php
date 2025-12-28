<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Carbon\Unit;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

/*
|--------------------------------------------------------------------------
| happenedBetween() Tests
|--------------------------------------------------------------------------
*/

it('happenedBetween includes events in range', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-10 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-15 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-01-20 12:00:00');
    $model->addEvent(TestEvent::Viewed);

    Carbon::setTestNow();

    $events = Event::happenedBetween(
        Carbon::parse('2024-01-12'),
        Carbon::parse('2024-01-18')
    )->get();

    expect($events)->toHaveCount(1);
});

it('happenedBetween excludes boundary events', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-15 00:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-15 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-01-16 00:00:00');
    $model->addEvent(TestEvent::Viewed);

    Carbon::setTestNow();

    $events = Event::happenedBetween(
        Carbon::parse('2024-01-15 00:00:00'),
        Carbon::parse('2024-01-15 23:59:59')
    )->get();

    expect($events)->toHaveCount(1);
    expect($events->first()->type)->toEqual(TestEvent::Updated->value);
});

it('happenedBetween with no events in range', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-01 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-30 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow();

    $events = Event::happenedBetween(
        Carbon::parse('2024-01-10'),
        Carbon::parse('2024-01-20')
    )->get();

    expect($events)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| happenedToday() Tests
|--------------------------------------------------------------------------
*/

it('happenedToday returns only todays events', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 08:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 14:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-14 12:00:00');
    $model->addEvent(TestEvent::Viewed);

    Carbon::setTestNow('2024-06-15 20:00:00');

    $events = Event::happenedToday()->get();

    expect($events)->toHaveCount(2);
});

it('happenedToday with no events today', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-14 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::happenedToday()->get();

    expect($events)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| happenedThisWeek() Tests
|--------------------------------------------------------------------------
*/

it('happenedThisWeek returns events from current week', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-19 12:00:00');

    Carbon::setTestNow('2024-06-17 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-18 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-16 12:00:00');
    $model->addEvent(TestEvent::Viewed);

    Carbon::setTestNow('2024-06-19 12:00:00');

    $events = Event::happenedThisWeek()->get();

    expect($events)->toHaveCount(2);
});

it('happenedThisWeek includes start of week', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-19 12:00:00');
    $startOfWeek = Carbon::now()->startOfWeek();

    Carbon::setTestNow($startOfWeek);
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-19 12:00:00');

    $events = Event::happenedThisWeek()->get();

    expect($events)->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| happenedThisMonth() Tests
|--------------------------------------------------------------------------
*/

it('happenedThisMonth returns events from current month', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');

    Carbon::setTestNow('2024-06-01 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-10 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-05-31 12:00:00');
    $model->addEvent(TestEvent::Viewed);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::happenedThisMonth()->get();

    expect($events)->toHaveCount(2);
});

it('happenedThisMonth includes start of month', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');
    $startOfMonth = Carbon::now()->startOfMonth();

    Carbon::setTestNow($startOfMonth);
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::happenedThisMonth()->get();

    expect($events)->toHaveCount(1);
});

it('happenedThisMonth excludes previous month', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-05-31 23:59:59');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-01 00:00:01');

    $events = Event::happenedThisMonth()->get();

    expect($events)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| Chaining Date Scopes Tests
|--------------------------------------------------------------------------
*/

it('can chain date scopes with type', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 10:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 14:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 16:00:00');

    $events = Event::ofType(TestEvent::Updated)->happenedToday()->get();

    expect($events)->toHaveCount(2);
});

it('can chain between with other scopes', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 10:00:00');
    $model->addEvent(TestEvent::Updated, ['field' => 'name']);

    Carbon::setTestNow('2024-06-15 12:00:00');
    $model->addEvent(TestEvent::Updated, ['field' => 'email']);

    Carbon::setTestNow('2024-06-15 14:00:00');
    $model->addEvent(TestEvent::Updated, ['field' => 'name']);

    Carbon::setTestNow();

    $events = Event::ofType(TestEvent::Updated)
        ->whereData(['field' => 'name'])
        ->happenedBetween(
            Carbon::parse('2024-06-15 09:00:00'),
            Carbon::parse('2024-06-15 13:00:00')
        )
        ->get();

    expect($events)->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| happenedInTheLast() Tests
|--------------------------------------------------------------------------
*/

it('happenedInTheLast days', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');

    Carbon::setTestNow('2024-06-12 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-05 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::happenedInTheLast(7, 'days')->get();

    expect($events)->toHaveCount(1);
});

it('happenedInTheLast hours', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');

    Carbon::setTestNow('2024-06-15 10:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-15 07:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::happenedInTheLast(3, 'hours')->get();

    expect($events)->toHaveCount(1);
});

it('happenedInTheLast months', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');

    Carbon::setTestNow('2024-05-15 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-02-15 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::happenedInTheLast(3, 'months')->get();

    expect($events)->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| hasntHappenedInTheLast() Tests
|--------------------------------------------------------------------------
*/

it('hasntHappenedInTheLast days', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');

    Carbon::setTestNow('2024-06-12 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-05 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::hasntHappenedInTheLast(7, 'days')->get();

    expect($events)->toHaveCount(1);
    expect($events->first()->type)->toEqual(TestEvent::Updated->value);
});

it('hasntHappenedInTheLast weeks', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');

    Carbon::setTestNow('2024-06-08 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-05-25 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::hasntHappenedInTheLast(2, 'weeks')->get();

    expect($events)->toHaveCount(1);
});

it('happenedInTheLast with Unit enum', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');

    Carbon::setTestNow('2024-06-12 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-06-05 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::happenedInTheLast(7, Unit::Day)->get();

    expect($events)->toHaveCount(1);
});

it('hasntHappenedInTheLast with Unit enum', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-06-15 12:00:00');

    Carbon::setTestNow('2024-05-15 12:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-03-15 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-06-15 12:00:00');

    $events = Event::hasntHappenedInTheLast(2, Unit::Month)->get();

    expect($events)->toHaveCount(1);
});
