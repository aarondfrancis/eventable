<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

class TimezoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_happened_after_with_different_timezone(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Create event at specific UTC time
        Carbon::setTestNow('2024-06-15 10:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow();

        // Query with a timezone-aware Carbon instance
        $queryTime = Carbon::parse('2024-06-15 05:00:00', 'America/New_York'); // 09:00 UTC

        $events = Event::happenedAfter($queryTime)->get();

        $this->assertCount(1, $events);
    }

    public function test_happened_before_with_different_timezone(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Create event at specific UTC time
        Carbon::setTestNow('2024-06-15 10:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow();

        // Query with a timezone-aware Carbon instance
        $queryTime = Carbon::parse('2024-06-15 07:00:00', 'America/New_York'); // 11:00 UTC

        $events = Event::happenedBefore($queryTime)->get();

        $this->assertCount(1, $events);
    }

    public function test_happened_after_excludes_events_before_threshold(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 08:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-06-15 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow();

        // Use Pacific time: 3am PDT = 10:00 UTC
        $queryTime = Carbon::parse('2024-06-15 03:00:00', 'America/Los_Angeles');

        $events = Event::happenedAfter($queryTime)->get();

        // Only the 12:00 UTC event should be included
        $this->assertCount(1, $events);
    }

    public function test_happened_before_excludes_events_after_threshold(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 08:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-06-15 14:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow();

        // Use Pacific time: 3am PDT = 10:00 UTC
        $queryTime = Carbon::parse('2024-06-15 03:00:00', 'America/Los_Angeles');

        $events = Event::happenedBefore($queryTime)->get();

        // Only the 08:00 UTC event should be included
        $this->assertCount(1, $events);
    }

    public function test_chained_time_scopes_with_timezones(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 06:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-06-15 10:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 14:00:00');
        $model->addEvent(TestEvent::Viewed);

        Carbon::setTestNow();

        // 2am EST = 07:00 UTC, 8am EST = 13:00 UTC
        $after = Carbon::parse('2024-06-15 02:00:00', 'America/New_York');
        $before = Carbon::parse('2024-06-15 08:00:00', 'America/New_York');

        $events = Event::happenedAfter($after)->happenedBefore($before)->get();

        // Only the 10:00 UTC event should be included
        $this->assertCount(1, $events);
    }
}
