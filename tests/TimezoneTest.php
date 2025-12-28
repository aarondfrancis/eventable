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

    /*
    |--------------------------------------------------------------------------
    | happenedToday() with timezone parameter
    |--------------------------------------------------------------------------
    */
    public function test_happened_today_with_explicit_timezone(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Create event at 2024-06-15 03:00:00 UTC
        // This is 2024-06-14 23:00:00 in America/New_York (EDT, UTC-4)
        Carbon::setTestNow('2024-06-15 03:00:00');
        $model->addEvent(TestEvent::Created);

        // Create event at 2024-06-15 10:00:00 UTC
        // This is 2024-06-15 06:00:00 in America/New_York
        Carbon::setTestNow('2024-06-15 10:00:00');
        $model->addEvent(TestEvent::Updated);

        // Query at 2024-06-15 12:00:00 UTC (08:00 in New York)
        Carbon::setTestNow('2024-06-15 12:00:00');

        // Without timezone: "today" in UTC is June 15
        $eventsUtc = Event::happenedToday()->get();
        $this->assertCount(2, $eventsUtc);

        // With New York timezone: "today" is June 15 in New York
        // June 15 00:00 EDT = June 15 04:00 UTC
        // The 03:00 UTC event is June 14 in New York, so it should be excluded
        $eventsNy = Event::happenedToday('America/New_York')->get();
        $this->assertCount(1, $eventsNy);
    }

    public function test_happened_today_timezone_boundary(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Create event right at the boundary
        // 2024-06-15 04:00:00 UTC = 2024-06-15 00:00:00 EDT (midnight in New York)
        Carbon::setTestNow('2024-06-15 04:00:00');
        $model->addEvent(TestEvent::Created);

        // Query later that day
        Carbon::setTestNow('2024-06-15 20:00:00');

        // This event should be included for New York's "today"
        $eventsNy = Event::happenedToday('America/New_York')->get();
        $this->assertCount(1, $eventsNy);
    }

    /*
    |--------------------------------------------------------------------------
    | happenedThisWeek() with timezone parameter
    |--------------------------------------------------------------------------
    */
    public function test_happened_this_week_with_explicit_timezone(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Monday June 17, 2024
        // In Tokyo (UTC+9), Sunday June 16 23:00 = Monday June 17 08:00 JST
        // So an event at Sunday 15:00 UTC is Sunday 00:00 JST (not this week in Tokyo)

        // Event on Sunday June 16 at 14:00 UTC (Sunday 23:00 in Tokyo)
        Carbon::setTestNow('2024-06-16 14:00:00');
        $model->addEvent(TestEvent::Created);

        // Event on Monday June 17 at 02:00 UTC (Monday 11:00 in Tokyo)
        Carbon::setTestNow('2024-06-17 02:00:00');
        $model->addEvent(TestEvent::Updated);

        // Query on Wednesday June 19 at 12:00 UTC
        Carbon::setTestNow('2024-06-19 12:00:00');

        // In UTC, both events are in this week (week starts Monday June 17)
        // Wait - Sunday June 16 is BEFORE the week starts (Monday June 17)
        $eventsUtc = Event::happenedThisWeek()->get();
        $this->assertCount(1, $eventsUtc); // Only Monday's event

        // In Tokyo, week starts Monday June 17 00:00 JST = June 16 15:00 UTC
        // The 14:00 UTC event is before the week start in Tokyo too
        $eventsTokyo = Event::happenedThisWeek('Asia/Tokyo')->get();
        $this->assertCount(1, $eventsTokyo);
    }

    /*
    |--------------------------------------------------------------------------
    | happenedThisMonth() with timezone parameter
    |--------------------------------------------------------------------------
    */
    public function test_happened_this_month_with_explicit_timezone(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Event on May 31 at 22:00 UTC
        // In UTC this is still May
        // In Tokyo (UTC+9) this is June 1 at 07:00
        Carbon::setTestNow('2024-05-31 22:00:00');
        $model->addEvent(TestEvent::Created);

        // Event clearly in June
        Carbon::setTestNow('2024-06-15 12:00:00');
        $model->addEvent(TestEvent::Updated);

        // Query on June 15
        Carbon::setTestNow('2024-06-15 12:00:00');

        // In UTC, only the June 15 event is in "this month"
        $eventsUtc = Event::happenedThisMonth()->get();
        $this->assertCount(1, $eventsUtc);

        // In Tokyo, June starts at May 31 15:00 UTC
        // So the May 31 22:00 UTC event IS in June for Tokyo
        $eventsTokyo = Event::happenedThisMonth('Asia/Tokyo')->get();
        $this->assertCount(2, $eventsTokyo);
    }
}
