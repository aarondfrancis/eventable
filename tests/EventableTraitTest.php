<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;

it('can add event to model', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(TestEvent::Created);

    expect($event)->toBeInstanceOf(Event::class);
    expect($event->type)->toBe(TestEvent::Created->value);
    expect($event->eventable_id)->toBe($model->id);
    expect($event->eventable_type)->toBe(TestModel::class);
});

it('can add event with data', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(TestEvent::Updated, ['field' => 'name', 'old' => 'Old', 'new' => 'New']);

    expect($event->data)->toBe(['field' => 'name', 'old' => 'Old', 'new' => 'New']);
});

it('can add event with null data', function () {
    $model = TestModel::create(['name' => 'Test']);

    $event = $model->addEvent(TestEvent::Viewed);

    expect($event->data)->toBeNull();
});

it('returns all events via relationship', function () {
    $model = TestModel::create(['name' => 'Test']);

    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);
    $model->addEvent(TestEvent::Viewed);

    expect($model->events)->toHaveCount(3);
});

it('scopes events to model', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->addEvent(TestEvent::Created);
    $model1->addEvent(TestEvent::Updated);
    $model2->addEvent(TestEvent::Created);

    expect($model1->events)->toHaveCount(2);
    expect($model2->events)->toHaveCount(1);
});

it('filters with whereEventHasHappened scope', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);
    $model3 = TestModel::create(['name' => 'Model 3']);

    $model1->addEvent(TestEvent::Exported);
    $model2->addEvent(TestEvent::Viewed);

    $exported = TestModel::whereEventHasHappened(TestEvent::Exported)->get();

    expect($exported)->toHaveCount(1);
    expect($exported->first()->id)->toBe($model1->id);
});

it('filters with whereEventHasntHappened scope', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);
    $model3 = TestModel::create(['name' => 'Model 3']);

    $model1->addEvent(TestEvent::Exported);

    $notExported = TestModel::whereEventHasntHappened(TestEvent::Exported)->get();

    expect($notExported)->toHaveCount(2);
    expect($notExported->contains('id', $model1->id))->toBeFalse();
});

it('filters whereEventHasHappened with data', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->addEvent(TestEvent::Updated, ['field' => 'name']);
    $model2->addEvent(TestEvent::Updated, ['field' => 'email']);

    $nameUpdated = TestModel::whereEventHasHappened(TestEvent::Updated, ['field' => 'name'])->get();

    expect($nameUpdated)->toHaveCount(1);
    expect($nameUpdated->first()->id)->toBe($model1->id);
});

it('filters whereEventHasntHappened with data', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);
    $model3 = TestModel::create(['name' => 'Model 3']);

    $model1->addEvent(TestEvent::Updated, ['field' => 'name']);
    $model2->addEvent(TestEvent::Updated, ['field' => 'email']);

    $notNameUpdated = TestModel::whereEventHasntHappened(TestEvent::Updated, ['field' => 'name'])->get();

    expect($notNameUpdated)->toHaveCount(2);
    expect($notNameUpdated->contains('id', $model1->id))->toBeFalse();
});
