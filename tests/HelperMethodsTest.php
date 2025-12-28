<?php

use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

/*
|--------------------------------------------------------------------------
| hasEvent() Tests
|--------------------------------------------------------------------------
*/

it('hasEvent returns true when event exists', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created);

    expect($model->hasEvent(TestEvent::Created))->toBeTrue();
});

it('hasEvent returns false when event does not exist', function () {
    $model = TestModel::create(['name' => 'Test']);

    expect($model->hasEvent(TestEvent::Created))->toBeFalse();
});

it('hasEvent with data matching', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Updated, ['field' => 'name']);
    $model->addEvent(TestEvent::Updated, ['field' => 'email']);

    expect($model->hasEvent(TestEvent::Updated, ['field' => 'name']))->toBeTrue();
    expect($model->hasEvent(TestEvent::Updated, ['field' => 'email']))->toBeTrue();
    expect($model->hasEvent(TestEvent::Updated, ['field' => 'phone']))->toBeFalse();
});

it('hasEvent is scoped to model', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->addEvent(TestEvent::Exported);

    expect($model1->hasEvent(TestEvent::Exported))->toBeTrue();
    expect($model2->hasEvent(TestEvent::Exported))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| latestEvent() Tests
|--------------------------------------------------------------------------
*/

it('latestEvent returns most recent event', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-01 10:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-01 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-01-01 14:00:00');
    $latest = $model->addEvent(TestEvent::Viewed);

    expect($model->latestEvent()->id)->toBe($latest->id);
});

it('latestEvent with type filter', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-01 10:00:00');
    $created = $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-01 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-01-01 14:00:00');
    $model->addEvent(TestEvent::Viewed);

    $latestCreated = $model->latestEvent(TestEvent::Created);

    expect($latestCreated->id)->toBe($created->id);
});

it('latestEvent returns null when no events', function () {
    $model = TestModel::create(['name' => 'Test']);

    expect($model->latestEvent())->toBeNull();
    expect($model->latestEvent(TestEvent::Created))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| firstEvent() Tests
|--------------------------------------------------------------------------
*/

it('firstEvent returns oldest event', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-01 10:00:00');
    $first = $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-01 12:00:00');
    $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-01-01 14:00:00');
    $model->addEvent(TestEvent::Viewed);

    expect($model->firstEvent()->id)->toBe($first->id);
});

it('firstEvent with type filter', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-01 10:00:00');
    $model->addEvent(TestEvent::Created);

    Carbon::setTestNow('2024-01-01 12:00:00');
    $firstUpdated = $model->addEvent(TestEvent::Updated);

    Carbon::setTestNow('2024-01-01 14:00:00');
    $model->addEvent(TestEvent::Updated);

    expect($model->firstEvent(TestEvent::Updated)->id)->toBe($firstUpdated->id);
});

it('firstEvent returns null when no events', function () {
    $model = TestModel::create(['name' => 'Test']);

    expect($model->firstEvent())->toBeNull();
    expect($model->firstEvent(TestEvent::Created))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| eventCount() Tests
|--------------------------------------------------------------------------
*/

it('eventCount returns total count', function () {
    $model = TestModel::create(['name' => 'Test']);

    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Viewed);

    expect($model->eventCount())->toBe(4);
});

it('eventCount with type filter', function () {
    $model = TestModel::create(['name' => 'Test']);

    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Viewed);

    expect($model->eventCount(TestEvent::Updated))->toBe(3);
    expect($model->eventCount(TestEvent::Created))->toBe(1);
    expect($model->eventCount(TestEvent::Exported))->toBe(0);
});

it('eventCount returns zero when no events', function () {
    $model = TestModel::create(['name' => 'Test']);

    expect($model->eventCount())->toBe(0);
    expect($model->eventCount(TestEvent::Created))->toBe(0);
});

it('eventCount is scoped to model', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->addEvent(TestEvent::Created);
    $model1->addEvent(TestEvent::Updated);
    $model2->addEvent(TestEvent::Created);

    expect($model1->eventCount())->toBe(2);
    expect($model2->eventCount())->toBe(1);
});
