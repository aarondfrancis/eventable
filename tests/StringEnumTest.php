<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\StringEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;

it('can add string backed enum event', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(StringEvent::UserCreated);

    expect($event->type)->toBe('user.created');
});

it('can add string backed enum event with data', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(StringEvent::UserUpdated, ['field' => 'email']);

    expect($event->type)->toBe('user.updated');
    expect($event->data)->toBe(['field' => 'email']);
});

it('ofType scope with string enum', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(StringEvent::UserCreated);
    $model->addEvent(StringEvent::UserUpdated);
    $model->addEvent(StringEvent::UserDeleted);

    $events = Event::ofType(StringEvent::UserCreated)->get();

    expect($events)->toHaveCount(1);
    expect($events->first()->type)->toBe('user.created');
});

it('ofType scope with string value', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(StringEvent::UserCreated);
    $model->addEvent(StringEvent::UserUpdated);

    $events = Event::ofType('user.updated')->get();

    expect($events)->toHaveCount(1);
});

it('ofType scope with array of string values', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(StringEvent::UserCreated);
    $model->addEvent(StringEvent::UserUpdated);
    $model->addEvent(StringEvent::UserDeleted);

    $events = Event::ofType(['user.created', 'user.deleted'])->get();

    expect($events)->toHaveCount(2);
});

it('whereEventHasHappened with string enum', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->addEvent(StringEvent::UserCreated);
    $model2->addEvent(StringEvent::UserUpdated);

    $models = TestModel::whereEventHasHappened(StringEvent::UserCreated)->get();

    expect($models)->toHaveCount(1);
    expect($models->first()->id)->toBe($model1->id);
});

it('whereEventHasntHappened with string enum', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);
    $model3 = TestModel::create(['name' => 'Model 3']);

    $model1->addEvent(StringEvent::UserDeleted);

    $models = TestModel::whereEventHasntHappened(StringEvent::UserDeleted)->get();

    expect($models)->toHaveCount(2);
});
