<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\PruneableEventDiscovery;
use AaronFrancis\Eventable\Tests\Fixtures\CombinedPruneEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

class CombinedPruneTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_combined_before_and_keep_prunes_correctly(): void
    {
        PruneableEventDiscovery::register(CombinedPruneEvent::class);

        $model = TestModel::create(['name' => 'Test']);
        $now = Carbon::now();

        // Create 6 events: 3 older than 7 days, 3 within 7 days
        // Older events (should be pruned, but keep last 3 per model)
        for ($i = 0; $i < 3; $i++) {
            Carbon::setTestNow($now->copy()->subDays(10 + $i));
            Event::create([
                'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
                'eventable_id' => $model->id,
                'eventable_type' => TestModel::class,
            ]);
        }

        // Recent events (should be kept regardless)
        for ($i = 0; $i < 3; $i++) {
            Carbon::setTestNow($now->copy()->subDays(1 + $i));
            Event::create([
                'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
                'eventable_id' => $model->id,
                'eventable_type' => TestModel::class,
            ]);
        }

        Carbon::setTestNow($now);

        // Before pruning: 6 events
        $this->assertEquals(6, Event::count());

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        // After pruning: 3 recent events remain (older ones pruned, keeping top 3)
        $this->assertEquals(3, Event::count());

        // Verify the remaining events are the recent ones
        $remainingEvents = Event::orderBy('created_at', 'desc')->get();
        foreach ($remainingEvents as $event) {
            $this->assertTrue($event->created_at->greaterThan($now->copy()->subDays(7)));
        }
    }

    public function test_keep_without_vary_on_data_treats_all_data_same(): void
    {
        PruneableEventDiscovery::register(CombinedPruneEvent::class);

        $model = TestModel::create(['name' => 'Test']);
        $now = Carbon::now();

        // Create 10 events with different data values
        for ($i = 0; $i < 10; $i++) {
            Carbon::setTestNow($now->copy()->subDays(10 - $i));
            Event::create([
                'type' => CombinedPruneEvent::KeepLast5NoVaryOnData->value,
                'eventable_id' => $model->id,
                'eventable_type' => TestModel::class,
                'data' => json_encode(['variant' => chr(65 + $i)]), // A, B, C, D, E, F, G, H, I, J
            ]);
        }

        Carbon::setTestNow($now);

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        // Should keep only 5 total, not 5 per variant
        $this->assertEquals(5, Event::count());
    }

    public function test_combined_conditions_across_multiple_models(): void
    {
        PruneableEventDiscovery::register(CombinedPruneEvent::class);

        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $now = Carbon::now();

        // Model 1: 5 old events
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow($now->copy()->subDays(20 + $i));
            Event::create([
                'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
                'eventable_id' => $model1->id,
                'eventable_type' => TestModel::class,
            ]);
        }

        // Model 2: 4 old events
        for ($i = 0; $i < 4; $i++) {
            Carbon::setTestNow($now->copy()->subDays(15 + $i));
            Event::create([
                'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
                'eventable_id' => $model2->id,
                'eventable_type' => TestModel::class,
            ]);
        }

        Carbon::setTestNow($now);

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        // Model 1 should have 3 (kept last 3), Model 2 should have 3 (kept last 3)
        $this->assertEquals(3, Event::where('eventable_id', $model1->id)->count());
        $this->assertEquals(3, Event::where('eventable_id', $model2->id)->count());
    }

    public function test_before_date_respected_even_with_keep(): void
    {
        PruneableEventDiscovery::register(CombinedPruneEvent::class);

        $model = TestModel::create(['name' => 'Test']);
        $now = Carbon::now();

        // Create 5 events within the last 7 days (should all be kept)
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow($now->copy()->subDays(1 + $i));
            Event::create([
                'type' => CombinedPruneEvent::KeepLast3OlderThan7Days->value,
                'eventable_id' => $model->id,
                'eventable_type' => TestModel::class,
            ]);
        }

        Carbon::setTestNow($now);

        $this->artisan('eventable:prune')
            ->assertExitCode(0);

        // All 5 should remain because they're all within 7 days
        $this->assertEquals(5, Event::count());
    }
}
