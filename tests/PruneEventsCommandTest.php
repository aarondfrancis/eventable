<?php

use AaronFrancis\Eventable\EventTypeRegistry;
use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\PruneableTestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('fails when no pruneable enums found', function () {
    EventTypeRegistry::clear();

    $this->artisan('eventable:prune')
        ->expectsOutput('No PruneableEvent enums found. Register event enums in config/eventable.php event_types.')
        ->assertExitCode(1);
});

it('skips non pruneable events', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Created);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(1);
});

it('prunes events older than before date', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    Carbon::setTestNow($now->copy()->subDays(45));
    Event::create([
        'type_class' => 'pruneable',
        'type' => PruneableTestEvent::PruneOlderThan30Days->value,
        'eventable_id' => $model->id,
        'eventable_type' => TestModel::class,
    ]);

    Carbon::setTestNow($now->copy()->subDays(15));
    Event::create([
        'type_class' => 'pruneable',
        'type' => PruneableTestEvent::PruneOlderThan30Days->value,
        'eventable_id' => $model->id,
        'eventable_type' => TestModel::class,
    ]);

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(1);
});

it('keeps last n events', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    for ($i = 0; $i < 10; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        Event::create([
            'type_class' => 'pruneable',
            'type' => PruneableTestEvent::KeepLast5->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(5);
});

it('keeps last n per model', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);
    $now = Carbon::now();

    for ($i = 0; $i < 8; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        Event::create([
            'type_class' => 'pruneable',
            'type' => PruneableTestEvent::KeepLast5->value,
            'eventable_id' => $model1->id,
            'eventable_type' => TestModel::class,
        ]);
    }

    for ($i = 0; $i < 4; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        Event::create([
            'type_class' => 'pruneable',
            'type' => PruneableTestEvent::KeepLast5->value,
            'eventable_id' => $model2->id,
            'eventable_type' => TestModel::class,
        ]);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::where('eventable_id', $model1->id)->count())->toBe(5);
    expect(Event::where('eventable_id', $model2->id)->count())->toBe(4);
});

it('vary on data keeps separate counts', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    for ($i = 0; $i < 5; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        Event::create([
            'type_class' => 'pruneable',
            'type' => PruneableTestEvent::KeepLast3VaryOnData->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
            'data' => json_encode(['variant' => 'A']),
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        Event::create([
            'type_class' => 'pruneable',
            'type' => PruneableTestEvent::KeepLast3VaryOnData->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
            'data' => json_encode(['variant' => 'B']),
        ]);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(6);
});

it('dry run does not delete', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    Carbon::setTestNow($now->copy()->subDays(45));
    Event::create([
        'type_class' => 'pruneable',
        'type' => PruneableTestEvent::PruneOlderThan30Days->value,
        'eventable_id' => $model->id,
        'eventable_type' => TestModel::class,
    ]);
    Carbon::setTestNow($now);

    $this->artisan('eventable:prune', ['--dry-run' => true])
        ->expectsOutputToContain('would be pruned')
        ->assertExitCode(0);

    expect(Event::count())->toBe(1);
});

it('skips events with null prune config', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    Carbon::setTestNow($now->copy()->subDays(100));
    Event::create([
        'type_class' => 'pruneable',
        'type' => PruneableTestEvent::KeepForever->value,
        'eventable_id' => $model->id,
        'eventable_type' => TestModel::class,
    ]);
    Event::create([
        'type_class' => 'pruneable',
        'type' => PruneableTestEvent::NoPruneConfig->value,
        'eventable_id' => $model->id,
        'eventable_type' => TestModel::class,
    ]);
    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(2);
});

it('outputs correct counts', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    for ($i = 0; $i < 3; $i++) {
        Carbon::setTestNow($now->copy()->subDays(45));
        Event::create([
            'type_class' => 'pruneable',
            'type' => PruneableTestEvent::PruneOlderThan30Days->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);
    }
    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->expectsOutputToContain('3 records pruned')
        ->assertExitCode(0);
});
