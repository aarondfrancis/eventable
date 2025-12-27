<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\StringEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StringEnumTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Drop and recreate the events table with string type for string-backed enums
        // This avoids needing doctrine/dbal for column changes in Laravel 10
        Schema::dropIfExists('events');
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedBigInteger('eventable_id');
            $table->string('eventable_type');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['eventable_id', 'eventable_type']);
            $table->index(['eventable_type', 'type']);
            $table->index(['type', 'created_at']);
        });
    }

    public function test_can_add_string_backed_enum_event(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(StringEvent::UserCreated);

        $this->assertEquals('user.created', $event->type);
    }

    public function test_can_add_string_backed_enum_event_with_data(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $event = $model->addEvent(StringEvent::UserUpdated, ['field' => 'email']);

        $this->assertEquals('user.updated', $event->type);
        $this->assertEquals(['field' => 'email'], $event->data);
    }

    public function test_scope_of_type_with_string_enum(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(StringEvent::UserCreated);
        $model->addEvent(StringEvent::UserUpdated);
        $model->addEvent(StringEvent::UserDeleted);

        $events = Event::ofType(StringEvent::UserCreated)->get();

        $this->assertCount(1, $events);
        $this->assertEquals('user.created', $events->first()->type);
    }

    public function test_scope_of_type_with_string_value(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(StringEvent::UserCreated);
        $model->addEvent(StringEvent::UserUpdated);

        $events = Event::ofType('user.updated')->get();

        $this->assertCount(1, $events);
    }

    public function test_scope_of_type_with_array_of_string_values(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(StringEvent::UserCreated);
        $model->addEvent(StringEvent::UserUpdated);
        $model->addEvent(StringEvent::UserDeleted);

        $events = Event::ofType(['user.created', 'user.deleted'])->get();

        $this->assertCount(2, $events);
    }

    public function test_where_event_has_happened_with_string_enum(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        $model1->addEvent(StringEvent::UserCreated);
        $model2->addEvent(StringEvent::UserUpdated);

        $models = TestModel::whereEventHasHappened(StringEvent::UserCreated)->get();

        $this->assertCount(1, $models);
        $this->assertEquals($model1->id, $models->first()->id);
    }

    public function test_where_event_hasnt_happened_with_string_enum(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        $model1->addEvent(StringEvent::UserDeleted);

        $models = TestModel::whereEventHasntHappened(StringEvent::UserDeleted)->get();

        $this->assertCount(2, $models);
    }
}
