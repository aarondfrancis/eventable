<?php

use AaronFrancis\Eventable\EventTypeRegistry;
use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\AnotherModel;
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

it('keeps the newest ids when timestamps tie', function () {
    $model = TestModel::create(['name' => 'Test']);

    Carbon::setTestNow('2024-01-15 12:00:00');

    $events = collect(range(1, 6))->map(fn () => $model->addEvent(PruneableTestEvent::KeepLast5));

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(5);
    expect(Event::orderBy('id')->pluck('id')->all())->toEqual(
        $events->pluck('id')->slice(1)->values()->all()
    );
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

it('keeps last n separately for different eventable types with the same id', function () {
    $testModel = TestModel::create(['name' => 'Test']);
    $anotherModel = AnotherModel::create(['title' => 'Another']);
    $now = Carbon::now();

    expect($testModel->id)->toBe(1);
    expect($anotherModel->id)->toBe(1);

    for ($i = 0; $i < 7; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        $testModel->addEvent(PruneableTestEvent::KeepLast5);
    }

    for ($i = 0; $i < 7; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        $anotherModel->addEvent(PruneableTestEvent::KeepLast5);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::where('eventable_type', TestModel::class)->count())->toBe(5);
    expect(Event::where('eventable_type', AnotherModel::class)->count())->toBe(5);
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
            'data' => ['variant' => 'A'],
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        Event::create([
            'type_class' => 'pruneable',
            'type' => PruneableTestEvent::KeepLast3VaryOnData->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
            'data' => ['variant' => 'B'],
        ]);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(6);
});

it('vary on data treats equivalent json objects as the same partition', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    $payloads = [
        ['variant' => 'A', 'context' => ['page' => 'home', 'slot' => 1]],
        ['context' => ['slot' => 1, 'page' => 'home'], 'variant' => 'A'],
        ['variant' => 'A', 'context' => ['page' => 'home', 'slot' => 1]],
        ['context' => ['slot' => 1, 'page' => 'home'], 'variant' => 'A'],
    ];

    foreach ($payloads as $index => $payload) {
        Carbon::setTestNow($now->copy()->subDays(10 - $index));
        $model->addEvent(PruneableTestEvent::KeepLast3VaryOnData, $payload);
    }

    Carbon::setTestNow($now);

    $storedEvents = Event::orderBy('id')->get();

    expect($storedEvents[0]->getRawOriginal('data'))->toBe($storedEvents[1]->getRawOriginal('data'));

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(3);
});

it('vary on data keeps json lists with different ordering in separate partitions', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    foreach (range(1, 4) as $index) {
        Carbon::setTestNow($now->copy()->subDays(10 - $index));
        $model->addEvent(PruneableTestEvent::KeepLast3VaryOnData, ['steps' => ['one', 'two']]);
    }

    foreach (range(1, 4) as $index) {
        Carbon::setTestNow($now->copy()->subDays(5 - $index));
        $model->addEvent(PruneableTestEvent::KeepLast3VaryOnData, ['steps' => ['two', 'one']]);
    }

    Carbon::setTestNow($now);

    $storedEvents = Event::orderBy('id')->get();

    expect($storedEvents[0]->getRawOriginal('data'))->not->toBe($storedEvents[4]->getRawOriginal('data'));

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

it('dry run reports the same keep-based prune count as the real prune', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    for ($i = 0; $i < 7; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        $model->addEvent(PruneableTestEvent::KeepLast5);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune', ['--dry-run' => true])
        ->expectsOutputToContain('Event KeepLast5: 2 records to prune.')
        ->expectsOutputToContain('Total: 2 records would be pruned.')
        ->assertExitCode(0);

    expect(Event::count())->toBe(7);

    $this->artisan('eventable:prune')
        ->expectsOutputToContain('Event KeepLast5: 2 records pruned.')
        ->expectsOutputToContain('Total: 2 records pruned.')
        ->assertExitCode(0);

    expect(Event::count())->toBe(5);
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
