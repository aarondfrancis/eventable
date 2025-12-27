<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\Tests\Fixtures\PruneableTestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

class PruneEventsCommandTest extends TestCase
{
    public function test_fails_without_enum_configured(): void
    {
        config(['eventable.event_enum' => null]);

        $this->artisan('eventable:prune')
            ->expectsOutput('No event enum configured. Set eventable.event_enum in your config.')
            ->assertExitCode(1);
    }

    public function test_fails_with_nonexistent_enum(): void
    {
        config(['eventable.event_enum' => 'NonExistentEnum']);

        $this->artisan('eventable:prune')
            ->expectsOutput('The configured event enum [NonExistentEnum] does not exist.')
            ->assertExitCode(1);
    }

    public function test_skips_non_pruneable_events(): void
    {
        config(['eventable.event_enum' => TestEvent::class]);

        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Created);

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        $this->assertEquals(1, Event::count());
    }

    public function test_prunes_events_older_than_before_date(): void
    {
        config(['eventable.event_enum' => PruneableTestEvent::class]);

        $model = TestModel::create(['name' => 'Test']);

        // Create old event (should be pruned)
        Carbon::setTestNow(Carbon::now()->subDays(45));
        Event::create([
            'type' => PruneableTestEvent::PruneOlderThan30Days->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);

        // Create recent event (should be kept)
        Carbon::setTestNow(Carbon::now()->subDays(15));
        Event::create([
            'type' => PruneableTestEvent::PruneOlderThan30Days->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);

        Carbon::setTestNow();

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        $this->assertEquals(1, Event::count());
    }

    public function test_keeps_last_n_events(): void
    {
        config(['eventable.event_enum' => PruneableTestEvent::class]);

        $model = TestModel::create(['name' => 'Test']);

        // Create 10 events
        for ($i = 0; $i < 10; $i++) {
            Carbon::setTestNow(Carbon::now()->subDays(10 - $i));
            Event::create([
                'type' => PruneableTestEvent::KeepLast5->value,
                'eventable_id' => $model->id,
                'eventable_type' => TestModel::class,
            ]);
        }

        Carbon::setTestNow();

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        $this->assertEquals(5, Event::count());
    }

    public function test_keeps_last_n_per_model(): void
    {
        config(['eventable.event_enum' => PruneableTestEvent::class]);

        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        // Create 8 events for model1 and 4 for model2
        for ($i = 0; $i < 8; $i++) {
            Carbon::setTestNow(Carbon::now()->subDays(10 - $i));
            Event::create([
                'type' => PruneableTestEvent::KeepLast5->value,
                'eventable_id' => $model1->id,
                'eventable_type' => TestModel::class,
            ]);
        }

        for ($i = 0; $i < 4; $i++) {
            Carbon::setTestNow(Carbon::now()->subDays(10 - $i));
            Event::create([
                'type' => PruneableTestEvent::KeepLast5->value,
                'eventable_id' => $model2->id,
                'eventable_type' => TestModel::class,
            ]);
        }

        Carbon::setTestNow();

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        // Model1 should have 5, Model2 should have 4 (all kept)
        $this->assertEquals(5, Event::where('eventable_id', $model1->id)->count());
        $this->assertEquals(4, Event::where('eventable_id', $model2->id)->count());
    }

    public function test_vary_on_data_keeps_separate_counts(): void
    {
        config(['eventable.event_enum' => PruneableTestEvent::class]);

        $model = TestModel::create(['name' => 'Test']);

        // Create 5 events with data A
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(Carbon::now()->subDays(10 - $i));
            Event::create([
                'type' => PruneableTestEvent::KeepLast3VaryOnData->value,
                'eventable_id' => $model->id,
                'eventable_type' => TestModel::class,
                'data' => json_encode(['variant' => 'A']),
            ]);
        }

        // Create 5 events with data B
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(Carbon::now()->subDays(10 - $i));
            Event::create([
                'type' => PruneableTestEvent::KeepLast3VaryOnData->value,
                'eventable_id' => $model->id,
                'eventable_type' => TestModel::class,
                'data' => json_encode(['variant' => 'B']),
            ]);
        }

        Carbon::setTestNow();

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        // Should keep 3 of each variant = 6 total
        $this->assertEquals(6, Event::count());
    }

    public function test_dry_run_does_not_delete(): void
    {
        config(['eventable.event_enum' => PruneableTestEvent::class]);

        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow(Carbon::now()->subDays(45));
        Event::create([
            'type' => PruneableTestEvent::PruneOlderThan30Days->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);
        Carbon::setTestNow();

        $this->artisan('eventable:prune', ['--dry-run' => true])
            ->expectsOutputToContain('would be pruned')
            ->assertExitCode(0);

        $this->assertEquals(1, Event::count());
    }

    public function test_skips_events_with_null_prune_config(): void
    {
        config(['eventable.event_enum' => PruneableTestEvent::class]);

        $model = TestModel::create(['name' => 'Test']);

        Carbon::setTestNow(Carbon::now()->subDays(100));
        Event::create([
            'type' => PruneableTestEvent::KeepForever->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);
        Event::create([
            'type' => PruneableTestEvent::NoPruneConfig->value,
            'eventable_id' => $model->id,
            'eventable_type' => TestModel::class,
        ]);
        Carbon::setTestNow();

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        $this->assertEquals(2, Event::count());
    }

    public function test_outputs_correct_counts(): void
    {
        config(['eventable.event_enum' => PruneableTestEvent::class]);

        $model = TestModel::create(['name' => 'Test']);

        for ($i = 0; $i < 3; $i++) {
            Carbon::setTestNow(Carbon::now()->subDays(45));
            Event::create([
                'type' => PruneableTestEvent::PruneOlderThan30Days->value,
                'eventable_id' => $model->id,
                'eventable_type' => TestModel::class,
            ]);
        }
        Carbon::setTestNow();

        $this->artisan('eventable:prune')
            ->expectsOutputToContain('3 records pruned')
            ->assertExitCode(0);
    }
}
