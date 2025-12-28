<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Carbon\Unit;
use Illuminate\Support\Carbon;

class DateScopesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | happenedBetween() Tests
    |--------------------------------------------------------------------------
    */
    public function test_happened_between_includes_events_in_range(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-10 12:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-15 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-01-20 12:00:00');
        $model->addEvent(TestEvent::Viewed);

        Carbon::setTestNow();

        $events = Event::happenedBetween(
            Carbon::parse('2024-01-12'),
            Carbon::parse('2024-01-18')
        )->get();

        $this->assertCount(1, $events);
    }

    public function test_happened_between_excludes_boundary_events(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-15 00:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-15 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-01-16 00:00:00');
        $model->addEvent(TestEvent::Viewed);

        Carbon::setTestNow();

        // Between 00:00 and 23:59 of Jan 15 - should only get the middle event
        $events = Event::happenedBetween(
            Carbon::parse('2024-01-15 00:00:00'),
            Carbon::parse('2024-01-15 23:59:59')
        )->get();

        $this->assertCount(1, $events);
        $this->assertEquals(TestEvent::Updated->value, $events->first()->type);
    }

    public function test_happened_between_with_no_events_in_range(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-01-01 12:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-30 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow();

        $events = Event::happenedBetween(
            Carbon::parse('2024-01-10'),
            Carbon::parse('2024-01-20')
        )->get();

        $this->assertCount(0, $events);
    }

    /*
    |--------------------------------------------------------------------------
    | happenedToday() Tests
    |--------------------------------------------------------------------------
    */
    public function test_happened_today_returns_only_todays_events(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 08:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-06-15 14:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-14 12:00:00');
        $model->addEvent(TestEvent::Viewed);

        Carbon::setTestNow('2024-06-15 20:00:00');

        $events = Event::happenedToday()->get();

        $this->assertCount(2, $events);
    }

    public function test_happened_today_with_no_events_today(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-14 12:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-06-15 12:00:00');

        $events = Event::happenedToday()->get();

        $this->assertCount(0, $events);
    }

    /*
    |--------------------------------------------------------------------------
    | happenedThisWeek() Tests
    |--------------------------------------------------------------------------
    */
    public function test_happened_this_week_returns_events_from_current_week(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Assuming week starts on Monday
        // Set "now" to Wednesday June 19, 2024
        Carbon::setTestNow('2024-06-19 12:00:00');

        // Event on Monday (this week)
        Carbon::setTestNow('2024-06-17 12:00:00');
        $model->addEvent(TestEvent::Created);

        // Event on Tuesday (this week)
        Carbon::setTestNow('2024-06-18 12:00:00');
        $model->addEvent(TestEvent::Updated);

        // Event last week (Sunday)
        Carbon::setTestNow('2024-06-16 12:00:00');
        $model->addEvent(TestEvent::Viewed);

        // Reset to Wednesday
        Carbon::setTestNow('2024-06-19 12:00:00');

        $events = Event::happenedThisWeek()->get();

        $this->assertCount(2, $events);
    }

    public function test_happened_this_week_includes_start_of_week(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Wednesday June 19, 2024
        Carbon::setTestNow('2024-06-19 12:00:00');
        $startOfWeek = Carbon::now()->startOfWeek();

        // Event at exact start of week
        Carbon::setTestNow($startOfWeek);
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-06-19 12:00:00');

        $events = Event::happenedThisWeek()->get();

        $this->assertCount(1, $events);
    }

    /*
    |--------------------------------------------------------------------------
    | happenedThisMonth() Tests
    |--------------------------------------------------------------------------
    */
    public function test_happened_this_month_returns_events_from_current_month(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Set "now" to June 15, 2024
        Carbon::setTestNow('2024-06-15 12:00:00');

        // Event on June 1
        Carbon::setTestNow('2024-06-01 12:00:00');
        $model->addEvent(TestEvent::Created);

        // Event on June 10
        Carbon::setTestNow('2024-06-10 12:00:00');
        $model->addEvent(TestEvent::Updated);

        // Event in May (last month)
        Carbon::setTestNow('2024-05-31 12:00:00');
        $model->addEvent(TestEvent::Viewed);

        // Reset to June 15
        Carbon::setTestNow('2024-06-15 12:00:00');

        $events = Event::happenedThisMonth()->get();

        $this->assertCount(2, $events);
    }

    public function test_happened_this_month_includes_start_of_month(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 12:00:00');
        $startOfMonth = Carbon::now()->startOfMonth();

        // Event at exact start of month
        Carbon::setTestNow($startOfMonth);
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-06-15 12:00:00');

        $events = Event::happenedThisMonth()->get();

        $this->assertCount(1, $events);
    }

    public function test_happened_this_month_excludes_previous_month(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        // Create event on last day of May
        Carbon::setTestNow('2024-05-31 23:59:59');
        $model->addEvent(TestEvent::Created);

        // Query in June
        Carbon::setTestNow('2024-06-01 00:00:01');

        $events = Event::happenedThisMonth()->get();

        $this->assertCount(0, $events);
    }

    /*
    |--------------------------------------------------------------------------
    | Chaining Date Scopes Tests
    |--------------------------------------------------------------------------
    */
    public function test_can_chain_date_scopes_with_type(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 10:00:00');
        $model->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-06-15 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 14:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 16:00:00');

        $events = Event::ofType(TestEvent::Updated)->happenedToday()->get();

        $this->assertCount(2, $events);
    }

    public function test_can_chain_between_with_other_scopes(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 10:00:00');
        $model->addEvent(TestEvent::Updated, ['field' => 'name']);

        Carbon::setTestNow('2024-06-15 12:00:00');
        $model->addEvent(TestEvent::Updated, ['field' => 'email']);

        Carbon::setTestNow('2024-06-15 14:00:00');
        $model->addEvent(TestEvent::Updated, ['field' => 'name']);

        Carbon::setTestNow();

        $events = Event::ofType(TestEvent::Updated)
            ->whereData(['field' => 'name'])
            ->happenedBetween(
                Carbon::parse('2024-06-15 09:00:00'),
                Carbon::parse('2024-06-15 13:00:00')
            )
            ->get();

        $this->assertCount(1, $events);
    }

    /*
    |--------------------------------------------------------------------------
    | happenedInTheLast() Tests
    |--------------------------------------------------------------------------
    */
    public function test_happened_in_the_last_days(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Event 3 days ago
        Carbon::setTestNow('2024-06-12 12:00:00');
        $model->addEvent(TestEvent::Created);

        // Event 10 days ago
        Carbon::setTestNow('2024-06-05 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 12:00:00');

        $events = Event::happenedInTheLast(7, 'days')->get();

        $this->assertCount(1, $events);
    }

    public function test_happened_in_the_last_hours(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Event 2 hours ago
        Carbon::setTestNow('2024-06-15 10:00:00');
        $model->addEvent(TestEvent::Created);

        // Event 5 hours ago
        Carbon::setTestNow('2024-06-15 07:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 12:00:00');

        $events = Event::happenedInTheLast(3, 'hours')->get();

        $this->assertCount(1, $events);
    }

    public function test_happened_in_the_last_months(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Event 1 month ago
        Carbon::setTestNow('2024-05-15 12:00:00');
        $model->addEvent(TestEvent::Created);

        // Event 4 months ago
        Carbon::setTestNow('2024-02-15 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 12:00:00');

        $events = Event::happenedInTheLast(3, 'months')->get();

        $this->assertCount(1, $events);
    }

    /*
    |--------------------------------------------------------------------------
    | hasntHappenedInTheLast() Tests
    |--------------------------------------------------------------------------
    */
    public function test_hasnt_happened_in_the_last_days(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Event 3 days ago (should be excluded)
        Carbon::setTestNow('2024-06-12 12:00:00');
        $model->addEvent(TestEvent::Created);

        // Event 10 days ago (should be included)
        Carbon::setTestNow('2024-06-05 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 12:00:00');

        $events = Event::hasntHappenedInTheLast(7, 'days')->get();

        $this->assertCount(1, $events);
        $this->assertEquals(TestEvent::Updated->value, $events->first()->type);
    }

    public function test_hasnt_happened_in_the_last_weeks(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Event 1 week ago (should be excluded)
        Carbon::setTestNow('2024-06-08 12:00:00');
        $model->addEvent(TestEvent::Created);

        // Event 3 weeks ago (should be included)
        Carbon::setTestNow('2024-05-25 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 12:00:00');

        $events = Event::hasntHappenedInTheLast(2, 'weeks')->get();

        $this->assertCount(1, $events);
    }

    public function test_happened_in_the_last_with_unit_enum(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Event 3 days ago
        Carbon::setTestNow('2024-06-12 12:00:00');
        $model->addEvent(TestEvent::Created);

        // Event 10 days ago
        Carbon::setTestNow('2024-06-05 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Using Unit enum instead of string
        $events = Event::happenedInTheLast(7, Unit::Day)->get();

        $this->assertCount(1, $events);
    }

    public function test_hasnt_happened_in_the_last_with_unit_enum(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Event 1 month ago
        Carbon::setTestNow('2024-05-15 12:00:00');
        $model->addEvent(TestEvent::Created);

        // Event 3 months ago
        Carbon::setTestNow('2024-03-15 12:00:00');
        $model->addEvent(TestEvent::Updated);

        Carbon::setTestNow('2024-06-15 12:00:00');

        // Using Unit enum
        $events = Event::hasntHappenedInTheLast(2, Unit::Month)->get();

        $this->assertCount(1, $events);
    }
}
