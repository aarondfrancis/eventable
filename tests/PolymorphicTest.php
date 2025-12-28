<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\AnotherModel;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;

it('scopes events to model type', function () {
    $testModel = TestModel::create(['name' => 'Test Model']);
    $anotherModel = AnotherModel::create(['title' => 'Another Model']);

    $testModel->addEvent(TestEvent::Created);
    $testModel->addEvent(TestEvent::Updated);
    $anotherModel->addEvent(TestEvent::Created);

    expect($testModel->events)->toHaveCount(2);
    expect($anotherModel->events)->toHaveCount(1);
});

it('keeps events separate for same id different types', function () {
    $testModel = TestModel::create(['name' => 'Test Model']);
    $anotherModel = AnotherModel::create(['title' => 'Another Model']);

    expect($testModel->id)->toBe(1);
    expect($anotherModel->id)->toBe(1);

    $testModel->addEvent(TestEvent::Created, ['source' => 'test']);
    $anotherModel->addEvent(TestEvent::Created, ['source' => 'another']);

    expect($testModel->events)->toHaveCount(1);
    expect($anotherModel->events)->toHaveCount(1);

    expect($testModel->events->first()->data['source'])->toBe('test');
    expect($anotherModel->events->first()->data['source'])->toBe('another');
});

it('returns correct model type from eventable relationship', function () {
    $testModel = TestModel::create(['name' => 'Test Model']);
    $anotherModel = AnotherModel::create(['title' => 'Another Model']);

    $event1 = $testModel->addEvent(TestEvent::Created);
    $event2 = $anotherModel->addEvent(TestEvent::Updated);

    $freshEvent1 = Event::find($event1->id);
    $freshEvent2 = Event::find($event2->id);

    expect($freshEvent1->eventable)->toBeInstanceOf(TestModel::class);
    expect($freshEvent2->eventable)->toBeInstanceOf(AnotherModel::class);
});

it('whereEventHasHappened only queries correct model type', function () {
    $testModel1 = TestModel::create(['name' => 'Test 1']);
    $testModel2 = TestModel::create(['name' => 'Test 2']);
    $anotherModel = AnotherModel::create(['title' => 'Another']);

    $testModel1->addEvent(TestEvent::Exported);
    $anotherModel->addEvent(TestEvent::Exported);

    $testModels = TestModel::whereEventHasHappened(TestEvent::Exported)->get();

    expect($testModels)->toHaveCount(1);
    expect($testModels->first()->id)->toBe($testModel1->id);

    $anotherModels = AnotherModel::whereEventHasHappened(TestEvent::Exported)->get();

    expect($anotherModels)->toHaveCount(1);
    expect($anotherModels->first()->id)->toBe($anotherModel->id);
});

it('whereEventHasntHappened respects model type', function () {
    $testModel1 = TestModel::create(['name' => 'Test 1']);
    $testModel2 = TestModel::create(['name' => 'Test 2']);
    $anotherModel = AnotherModel::create(['title' => 'Another']);

    $testModel1->addEvent(TestEvent::Deleted);
    $anotherModel->addEvent(TestEvent::Deleted);

    $testModels = TestModel::whereEventHasntHappened(TestEvent::Deleted)->get();

    expect($testModels)->toHaveCount(1);
    expect($testModels->first()->id)->toBe($testModel2->id);
});

it('can query all events across model types', function () {
    $testModel = TestModel::create(['name' => 'Test']);
    $anotherModel = AnotherModel::create(['title' => 'Another']);

    $testModel->addEvent(TestEvent::Created);
    $anotherModel->addEvent(TestEvent::Created);
    $testModel->addEvent(TestEvent::Updated);

    $createdEvents = Event::ofType(TestEvent::Created)->get();

    expect($createdEvents)->toHaveCount(2);
});

it('events with data are isolated by model type', function () {
    $testModel = TestModel::create(['name' => 'Test']);
    $anotherModel = AnotherModel::create(['title' => 'Another']);

    $testModel->addEvent(TestEvent::Updated, ['field' => 'name']);
    $anotherModel->addEvent(TestEvent::Updated, ['field' => 'title']);

    $testModels = TestModel::whereEventHasHappened(TestEvent::Updated, ['field' => 'name'])->get();
    $anotherModels = AnotherModel::whereEventHasHappened(TestEvent::Updated, ['field' => 'title'])->get();

    expect($testModels)->toHaveCount(1);
    expect($anotherModels)->toHaveCount(1);

    $noResults = TestModel::whereEventHasHappened(TestEvent::Updated, ['field' => 'title'])->get();
    expect($noResults)->toHaveCount(0);
});
