<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\CustomEvent;
use AaronFrancis\Eventable\Tests\Fixtures\PrunableCustomEvent;
use AaronFrancis\Eventable\Tests\Fixtures\PruneableTestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('can use custom event model', function () {
    config(['eventable.model' => CustomEvent::class]);

    $model = TestModel::create(['name' => 'Test']);
    $event = $model->addEvent(TestEvent::Created);

    expect($event)->toBeInstanceOf(CustomEvent::class);
});

it('custom event model has custom attributes', function () {
    config(['eventable.model' => CustomEvent::class]);

    $model = TestModel::create(['name' => 'Test']);
    $event = $model->addEvent(TestEvent::Created);

    expect($event->custom_attribute)->toBe('custom_value');
});

it('events relationship uses custom model', function () {
    config(['eventable.model' => CustomEvent::class]);

    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);

    $events = $model->events;

    expect($events)->toHaveCount(2);
    expect($events->first())->toBeInstanceOf(CustomEvent::class);
});

it('uses custom table name', function () {
    config(['eventable.table' => 'activity_log']);

    $event = new Event;

    expect($event->getTable())->toBe('activity_log');
});

it('scopes work with custom model', function () {
    config(['eventable.model' => CustomEvent::class]);

    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created);
    $model->addEvent(TestEvent::Updated);

    $models = TestModel::whereEventHasHappened(TestEvent::Created)->get();

    expect($models)->toHaveCount(1);
});

it('prune command uses the configured event model query builder', function () {
    config(['eventable.model' => PrunableCustomEvent::class]);

    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    Carbon::setTestNow($now->copy()->subDays(45));
    $model->addEvent(PruneableTestEvent::PruneOlderThan30Days);

    Carbon::setTestNow($now);
    PrunableCustomEvent::resetQueryFlag();

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(PrunableCustomEvent::$newQueryWasCalled)->toBeTrue();
    expect(Event::count())->toBe(0);
});
