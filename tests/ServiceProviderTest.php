<?php

use AaronFrancis\Eventable\EventableServiceProvider;
use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Relations\Relation;

it('merges config', function () {
    expect(config('eventable'))->not->toBeNull();
    expect(config('eventable.table'))->toBe('events');
});

it('registers morph map', function () {
    $morphMap = Relation::morphMap();

    expect($morphMap)->toHaveKey('event');
    expect($morphMap['event'])->toBe(Event::class);
});

it('can disable morph map', function () {
    Relation::morphMap([], false);

    config(['eventable.register_morph_map' => false]);

    $this->app->register(EventableServiceProvider::class, true);

    $morphMap = Relation::morphMap();

    expect($morphMap)->not->toHaveKey('event');
});

it('supports custom morph alias', function () {
    Relation::morphMap([], false);

    config(['eventable.morph_alias' => 'custom_event']);

    $this->app->register(EventableServiceProvider::class, true);

    $morphMap = Relation::morphMap();

    expect($morphMap)->toHaveKey('custom_event');
    expect($morphMap['custom_event'])->toBe(Event::class);
});

it('uses custom event model in morph map', function () {
    Relation::morphMap([], false);

    config(['eventable.model' => TestModel::class]);

    $this->app->register(EventableServiceProvider::class, true);

    $morphMap = Relation::morphMap();

    expect($morphMap['event'])->toBe(TestModel::class);
});

it('registers prune command', function () {
    $commands = collect($this->app->make(Kernel::class)->all());

    expect($commands->has('eventable:prune'))->toBeTrue();
});
