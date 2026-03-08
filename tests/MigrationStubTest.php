<?php

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\StringKeyTestModel;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function loadEventableMigrationStub(): object
{
    return require __DIR__.'/../database/migrations/create_events_table.php.stub';
}

afterEach(function () {
    Schema::defaultMorphKeyType('int');
});

it('respects the configured morph key type for uuid models', function () {
    Schema::dropIfExists(config('eventable.table', 'events'));
    Schema::dropIfExists('string_key_test_models');

    Schema::defaultMorphKeyType('uuid');

    $migration = loadEventableMigrationStub();
    $migration->up();

    Schema::create('string_key_test_models', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->timestamps();
    });

    $model = StringKeyTestModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'String Key Model',
    ]);

    $event = $model->addEvent(TestEvent::Created);
    $freshEvent = Event::findOrFail($event->id);

    expect((string) $freshEvent->eventable_id)->toBe($model->id);

    Schema::dropIfExists('string_key_test_models');
});
