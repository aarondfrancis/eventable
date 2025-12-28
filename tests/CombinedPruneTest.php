<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\CombinedPruneEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('combined before and keep prunes correctly', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    // Older events (should be pruned, but keep last 3 per model)
    for ($i = 0; $i < 3; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 + $i));
        Event::create([
            'type_class' => 'combined',
            'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);
    }

    // Recent events (should be kept regardless)
    for ($i = 0; $i < 3; $i++) {
        Carbon::setTestNow($now->copy()->subDays(1 + $i));
        Event::create([
            'type_class' => 'combined',
            'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);
    }

    Carbon::setTestNow($now);

    expect(Event::count())->toBe(6);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(3);

    $remainingEvents = Event::orderBy('created_at', 'desc')->get();
    foreach ($remainingEvents as $event) {
        expect($event->created_at->greaterThan($now->copy()->subDays(7)))->toBeTrue();
    }
});

it('keep without vary on data treats all data same', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    for ($i = 0; $i < 10; $i++) {
        Carbon::setTestNow($now->copy()->subDays(10 - $i));
        Event::create([
            'type_class' => 'combined',
            'type' => CombinedPruneEvent::KeepLast5NoVaryOnData->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
            'data' => json_encode(['variant' => chr(65 + $i)]),
        ]);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(5);
});

it('combined conditions across multiple models', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);
    $now = Carbon::now();

    for ($i = 0; $i < 5; $i++) {
        Carbon::setTestNow($now->copy()->subDays(20 + $i));
        Event::create([
            'type_class' => 'combined',
            'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
            'eventable_id' => $model1->id,
            'eventable_type' => TestModel::class,
        ]);
    }

    for ($i = 0; $i < 4; $i++) {
        Carbon::setTestNow($now->copy()->subDays(15 + $i));
        Event::create([
            'type_class' => 'combined',
            'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
            'eventable_id' => $model2->id,
            'eventable_type' => TestModel::class,
        ]);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::where('eventable_id', $model1->id)->count())->toBe(3);
    expect(Event::where('eventable_id', $model2->id)->count())->toBe(3);
});

it('before date respected even with keep', function () {
    $model = TestModel::create(['name' => 'Test']);
    $now = Carbon::now();

    for ($i = 0; $i < 5; $i++) {
        Carbon::setTestNow($now->copy()->subDays(1 + $i));
        Event::create([
            'type_class' => 'combined',
            'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);
    }

    Carbon::setTestNow($now);

    $this->artisan('eventable:prune')
        ->assertExitCode(0);

    expect(Event::count())->toBe(5);
});
