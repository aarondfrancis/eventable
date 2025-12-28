<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

class HelperMethodsTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | hasEvent() Tests
    |--------------------------------------------------------------------------
    */
    public function test_has_event_returns_true_when_event_exists(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created);

        $this->assertTrue($model->hasEvent(TestEvent::Created));
    }

    public function test_has_event_returns_false_when_event_does_not_exist(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $this->assertFalse($model->hasEvent(TestEvent::Created));
    }

    public function test_has_event_with_data_matching(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Updated, ['field' => 'name']);
        $model->addEvent(TestEvent::Updated, ['field' => 'email']);

        $this->assertTrue($model->hasEvent(TestEvent::Updated, ['field' => 'name']));
        $this->assertTrue($model->hasEvent(TestEvent::Updated, ['field' => 'email']));
        $this->assertFalse($model->hasEvent(TestEvent::Updated, ['field' => 'phone']));
    }

    public function test_has_event_is_scoped_to_model(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        $model1->addEvent(TestEvent::Exported);

        $this->assertTrue($model1->hasEvent(TestEvent::Exported));
        $this->assertFalse($model2->hasEvent(TestEvent::Exported));
    }

    /*
    |--------------------------------------------------------------------------
    | latestEvent() Tests
    |--------------------------------------------------------------------------
    */
    public function test_latest_event_returns_most_recent_event(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-01 10:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-01 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-01-01 14:00:00');
        $latest = $model->addEvent(TestEvent::Viewed);

        $this->assertEquals($latest->id, $model->latestEvent()->id);
    }

    public function test_latest_event_with_type_filter(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-01 10:00:00');
        $created = $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-01 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-01-01 14:00:00');
        $model->addEvent(TestEvent::Viewed);

        $latestCreated = $model->latestEvent(TestEvent::Created);

        $this->assertEquals($created->id, $latestCreated->id);
    }

    public function test_latest_event_returns_null_when_no_events(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $this->assertNull($model->latestEvent());
        $this->assertNull($model->latestEvent(TestEvent::Created));
    }

    /*
    |--------------------------------------------------------------------------
    | firstEvent() Tests
    |--------------------------------------------------------------------------
    */
    public function test_first_event_returns_oldest_event(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-01 10:00:00');
        $first = $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-01 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-01-01 14:00:00');
        $model->addEvent(TestEvent::Viewed);

        $this->assertEquals($first->id, $model->firstEvent()->id);
    }

    public function test_first_event_with_type_filter(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-01 10:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-01 12:00:00');
        $firstUpdated = $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-01-01 14:00:00');
        $model->addEvent(TestEvent::Updated);

        $this->assertEquals($firstUpdated->id, $model->firstEvent(TestEvent::Updated)->id);
    }

    public function test_first_event_returns_null_when_no_events(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $this->assertNull($model->firstEvent());
        $this->assertNull($model->firstEvent(TestEvent::Created));
    }

    /*
    |--------------------------------------------------------------------------
    | eventCount() Tests
    |--------------------------------------------------------------------------
    */
    public function test_event_count_returns_total_count(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Viewed);

        $this->assertEquals(4, $model->eventCount());
    }

    public function test_event_count_with_type_filter(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $model->addEvent(TestEvent::Created);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Updated);
        $model->addEvent(TestEvent::Viewed);

        $this->assertEquals(3, $model->eventCount(TestEvent::Updated));
        $this->assertEquals(1, $model->eventCount(TestEvent::Created));
        $this->assertEquals(0, $model->eventCount(TestEvent::Exported));
    }

    public function test_event_count_returns_zero_when_no_events(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $this->assertEquals(0, $model->eventCount());
        $this->assertEquals(0, $model->eventCount(TestEvent::Created));
    }

    public function test_event_count_is_scoped_to_model(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        $model1->addEvent(TestEvent::Created);
        $model1->addEvent(TestEvent::Updated);
        $model2->addEvent(TestEvent::Created);

        $this->assertEquals(2, $model1->eventCount());
        $this->assertEquals(1, $model2->eventCount());
    }
}
