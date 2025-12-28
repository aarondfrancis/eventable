<?php

use AaronFrancis\Eventable\PruneConfig;
use Illuminate\Support\Carbon;

it('has default values', function () {
    $config = new PruneConfig;

    expect($config->before)->toBeNull();
    expect($config->keep)->toBe(0);
    expect($config->varyOnData)->toBeTrue();
});

it('accepts custom before', function () {
    $before = Carbon::now()->subDays(30);
    $config = new PruneConfig(before: $before);

    expect($config->before)->toBe($before);
});

it('accepts custom keep', function () {
    $config = new PruneConfig(keep: 5);

    expect($config->keep)->toBe(5);
});

it('accepts custom vary on data', function () {
    $config = new PruneConfig(varyOnData: false);

    expect($config->varyOnData)->toBeFalse();
});

it('accepts all custom values', function () {
    $before = Carbon::now()->subDays(7);
    $config = new PruneConfig(
        before: $before,
        keep: 10,
        varyOnData: false
    );

    expect($config->before)->toBe($before);
    expect($config->keep)->toBe(10);
    expect($config->varyOnData)->toBeFalse();
});

it('is readonly', function () {
    $config = new PruneConfig(keep: 5);

    $reflection = new ReflectionClass($config);

    expect($reflection->isReadOnly())->toBeTrue();
});
