<?php

use AaronFrancis\Eventable\Prune;
use AaronFrancis\Eventable\PruneConfig;
use Illuminate\Support\Carbon;

it('builds prune configs fluently', function () {
    $before = Carbon::now()->subDays(30);

    $config = Prune::before($before)
        ->keep(5)
        ->dontVaryOnData()
        ->toPruneConfig();

    expect($config)->toBeInstanceOf(PruneConfig::class);
    expect($config->before?->eq($before))->toBeTrue();
    expect($config->keep)->toBe(5);
    expect($config->varyOnData)->toBeFalse();
});

it('normalizes fluent prune builders into prune configs', function () {
    $config = PruneConfig::from(Prune::keep(3)->varyOnData());

    expect($config)->toBeInstanceOf(PruneConfig::class);
    expect($config->keep)->toBe(3);
    expect($config->varyOnData)->toBeTrue();
});

it('rejects invalid fluent prune methods', function () {
    Prune::invalid('nope');
})->throws(BadMethodCallException::class, 'Method [AaronFrancis\Eventable\Prune::invalid] does not exist.');

it('rejects non-positive keep values in the fluent builder', function () {
    Prune::keep(0);
})->throws(InvalidArgumentException::class, 'PruneConfig keep must be at least 1.');

it('requires at least one retention constraint when building a prune config', function () {
    Prune::varyOnData()->toPruneConfig();
})->throws(InvalidArgumentException::class, 'PruneConfig must define before and/or keep.');
