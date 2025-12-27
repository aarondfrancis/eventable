<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\AnotherModel;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;

class PolymorphicTest extends TestCase
{
    public function test_events_are_scoped_to_model_type(): void
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        $anotherModel = AnotherModel::create(['title' => 'Another Model']);

        $testModel->addEvent(TestEvent::Created);
        $testModel->addEvent(TestEvent::Updated);
        $anotherModel->addEvent(TestEvent::Created);

        $this->assertCount(2, $testModel->events);
        $this->assertCount(1, $anotherModel->events);
    }

    public function test_same_id_different_types_have_separate_events(): void
    {
        // Create models that might have the same ID
        $testModel = TestModel::create(['name' => 'Test Model']);
        $anotherModel = AnotherModel::create(['title' => 'Another Model']);

        // Both models have ID 1 (first of their type)
        $this->assertEquals(1, $testModel->id);
        $this->assertEquals(1, $anotherModel->id);

        $testModel->addEvent(TestEvent::Created, ['source' => 'test']);
        $anotherModel->addEvent(TestEvent::Created, ['source' => 'another']);

        // Each should only see their own event
        $this->assertCount(1, $testModel->events);
        $this->assertCount(1, $anotherModel->events);

        $this->assertEquals('test', $testModel->events->first()->data['source']);
        $this->assertEquals('another', $anotherModel->events->first()->data['source']);
    }

    public function test_eventable_relationship_returns_correct_model_type(): void
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        $anotherModel = AnotherModel::create(['title' => 'Another Model']);

        $event1 = $testModel->addEvent(TestEvent::Created);
        $event2 = $anotherModel->addEvent(TestEvent::Updated);

        $freshEvent1 = Event::find($event1->id);
        $freshEvent2 = Event::find($event2->id);

        $this->assertInstanceOf(TestModel::class, $freshEvent1->eventable);
        $this->assertInstanceOf(AnotherModel::class, $freshEvent2->eventable);
    }

    public function test_where_event_has_happened_only_queries_correct_model_type(): void
    {
        $testModel1 = TestModel::create(['name' => 'Test 1']);
        $testModel2 = TestModel::create(['name' => 'Test 2']);
        $anotherModel = AnotherModel::create(['title' => 'Another']);

        $testModel1->addEvent(TestEvent::Exported);
        $anotherModel->addEvent(TestEvent::Exported);

        // Query TestModel - should only find testModel1
        $testModels = TestModel::whereEventHasHappened(TestEvent::Exported)->get();

        $this->assertCount(1, $testModels);
        $this->assertEquals($testModel1->id, $testModels->first()->id);

        // Query AnotherModel - should only find anotherModel
        $anotherModels = AnotherModel::whereEventHasHappened(TestEvent::Exported)->get();

        $this->assertCount(1, $anotherModels);
        $this->assertEquals($anotherModel->id, $anotherModels->first()->id);
    }

    public function test_where_event_hasnt_happened_respects_model_type(): void
    {
        $testModel1 = TestModel::create(['name' => 'Test 1']);
        $testModel2 = TestModel::create(['name' => 'Test 2']);
        $anotherModel = AnotherModel::create(['title' => 'Another']);

        $testModel1->addEvent(TestEvent::Deleted);
        $anotherModel->addEvent(TestEvent::Deleted);

        // Query TestModel - testModel2 hasn't had Deleted event
        $testModels = TestModel::whereEventHasntHappened(TestEvent::Deleted)->get();

        $this->assertCount(1, $testModels);
        $this->assertEquals($testModel2->id, $testModels->first()->id);
    }

    public function test_can_query_all_events_across_model_types(): void
    {
        $testModel = TestModel::create(['name' => 'Test']);
        $anotherModel = AnotherModel::create(['title' => 'Another']);

        $testModel->addEvent(TestEvent::Created);
        $anotherModel->addEvent(TestEvent::Created);
        $testModel->addEvent(TestEvent::Updated);

        // Query all events of a specific type across all models
        $createdEvents = Event::ofType(TestEvent::Created)->get();

        $this->assertCount(2, $createdEvents);
    }

    public function test_events_with_data_are_isolated_by_model_type(): void
    {
        $testModel = TestModel::create(['name' => 'Test']);
        $anotherModel = AnotherModel::create(['title' => 'Another']);

        $testModel->addEvent(TestEvent::Updated, ['field' => 'name']);
        $anotherModel->addEvent(TestEvent::Updated, ['field' => 'title']);

        $testModels = TestModel::whereEventHasHappened(TestEvent::Updated, ['field' => 'name'])->get();
        $anotherModels = AnotherModel::whereEventHasHappened(TestEvent::Updated, ['field' => 'title'])->get();

        $this->assertCount(1, $testModels);
        $this->assertCount(1, $anotherModels);

        // Cross-check: TestModel should not find events with 'title' field
        $noResults = TestModel::whereEventHasHappened(TestEvent::Updated, ['field' => 'title'])->get();
        $this->assertCount(0, $noResults);
    }
}
