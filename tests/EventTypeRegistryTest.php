<?php

use AaronFrancis\Eventable\EventTypeRegistry;
use AaronFrancis\Eventable\Tests\Fixtures\StringEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;

afterEach(function () {
    config(['eventable.event_types' => []]);
});

it('uses config event types for lookups', function () {
    EventTypeRegistry::clear();
    config(['eventable.event_types' => ['test' => TestEvent::class]]);

    expect(EventTypeRegistry::getAlias(TestEvent::class))->toBe('test');
    expect(EventTypeRegistry::getClass('test'))->toBe(TestEvent::class);
    expect(EventTypeRegistry::isRegistered(TestEvent::class))->toBeTrue();
    expect(EventTypeRegistry::hasAlias('test'))->toBeTrue();
});

it('prefers manual registrations over config', function () {
    EventTypeRegistry::clear();
    config(['eventable.event_types' => ['test' => TestEvent::class]]);
    EventTypeRegistry::register('test', StringEvent::class);

    expect(EventTypeRegistry::getClass('test'))->toBe(StringEvent::class);
    expect(EventTypeRegistry::getAlias(StringEvent::class))->toBe('test');
});
