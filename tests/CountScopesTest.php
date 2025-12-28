<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

class CountScopesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | whereEventHasHappenedTimes() Tests
    |--------------------------------------------------------------------------
    */
    public function test_where_event_has_happened_times_exact_match(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        // Model 1: 3 views
        $model1->addEvent(TestEvent::Viewed);
        $model1->addEvent(TestEvent::Viewed);
        $model1->addEvent(TestEvent::Viewed);

        // Model 2: 2 views
        $model2->addEvent(TestEvent::Viewed);
        $model2->addEvent(TestEvent::Viewed);

        // Model 3: 3 views
        $model3->addEvent(TestEvent::Viewed);
        $model3->addEvent(TestEvent::Viewed);
        $model3->addEvent(TestEvent::Viewed);

        $modelsWithExactly3 = TestModel::whereEventHasHappenedTimes(TestEvent::Viewed, 3)->get();

        $this->assertCount(2, $modelsWithExactly3);
        $this->assertTrue($modelsWithExactly3->contains('id', $model1->id));
        $this->assertTrue($modelsWithExactly3->contains('id', $model3->id));
    }

    public function test_where_event_has_happened_times_zero(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        $model1->addEvent(TestEvent::Viewed);

        // Models with exactly 0 views should be model2
        $modelsWithZero = TestModel::whereEventHasHappenedTimes(TestEvent::Viewed, 0)->get();

        $this->assertCount(1, $modelsWithZero);
        $this->assertEquals($model2->id, $modelsWithZero->first()->id);
    }

    public function test_where_event_has_happened_times_with_data(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        // Model 1: 2 name updates
        $model1->addEvent(TestEvent::Updated, ['field' => 'name']);
        $model1->addEvent(TestEvent::Updated, ['field' => 'name']);
        $model1->addEvent(TestEvent::Updated, ['field' => 'email']);

        // Model 2: 1 name update
        $model2->addEvent(TestEvent::Updated, ['field' => 'name']);

        $modelsWithExactly2NameUpdates = TestModel::whereEventHasHappenedTimes(
            TestEvent::Updated,
            2,
            ['field' => 'name']
        )->get();

        $this->assertCount(1, $modelsWithExactly2NameUpdates);
        $this->assertEquals($model1->id, $modelsWithExactly2NameUpdates->first()->id);
    }

    /*
    |--------------------------------------------------------------------------
    | whereEventHasHappenedAtLeast() Tests
    |--------------------------------------------------------------------------
    */
    public function test_where_event_has_happened_at_least(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        // Model 1: 5 views
        for ($i = 0; $i < 5; $i++) {
            $model1->addEvent(TestEvent::Viewed);
        }

        // Model 2: 2 views
        $model2->addEvent(TestEvent::Viewed);
        $model2->addEvent(TestEvent::Viewed);

        // Model 3: 3 views
        for ($i = 0; $i < 3; $i++) {
            $model3->addEvent(TestEvent::Viewed);
        }

        $modelsWithAtLeast3 = TestModel::whereEventHasHappenedAtLeast(TestEvent::Viewed, 3)->get();

        $this->assertCount(2, $modelsWithAtLeast3);
        $this->assertTrue($modelsWithAtLeast3->contains('id', $model1->id));
        $this->assertTrue($modelsWithAtLeast3->contains('id', $model3->id));
    }

    public function test_where_event_has_happened_at_least_one(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        $model1->addEvent(TestEvent::Exported);
        $model3->addEvent(TestEvent::Exported);
        $model3->addEvent(TestEvent::Exported);

        $modelsWithAtLeast1 = TestModel::whereEventHasHappenedAtLeast(TestEvent::Exported, 1)->get();

        $this->assertCount(2, $modelsWithAtLeast1);
        $this->assertTrue($modelsWithAtLeast1->contains('id', $model1->id));
        $this->assertTrue($modelsWithAtLeast1->contains('id', $model3->id));
    }

    public function test_where_event_has_happened_at_least_with_data(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        // Model 1: 3 USD orders
        $model1->addEvent(TestEvent::Updated, ['currency' => 'USD']);
        $model1->addEvent(TestEvent::Updated, ['currency' => 'USD']);
        $model1->addEvent(TestEvent::Updated, ['currency' => 'USD']);
        $model1->addEvent(TestEvent::Updated, ['currency' => 'EUR']);

        // Model 2: 1 USD order
        $model2->addEvent(TestEvent::Updated, ['currency' => 'USD']);

        $modelsWithAtLeast2UsdOrders = TestModel::whereEventHasHappenedAtLeast(
            TestEvent::Updated,
            2,
            ['currency' => 'USD']
        )->get();

        $this->assertCount(1, $modelsWithAtLeast2UsdOrders);
        $this->assertEquals($model1->id, $modelsWithAtLeast2UsdOrders->first()->id);
    }

    /*
    |--------------------------------------------------------------------------
    | whereLatestEventIs() Tests
    |--------------------------------------------------------------------------
    */
    public function test_where_latest_event_is(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        Carbon::setTestNow('2024-01-01 10:00:00');
        $model1->addEvent(TestEvent::Created);
        $model2->addEvent(TestEvent::Created);
        $model3->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-01 12:00:00');
        $model1->addEvent(TestEvent::Updated);
        $model2->addEvent(TestEvent::Viewed);
        // Model 3's latest is still Created

        $modelsWhereLatestIsUpdated = TestModel::whereLatestEventIs(TestEvent::Updated)->get();

        $this->assertCount(1, $modelsWhereLatestIsUpdated);
        $this->assertEquals($model1->id, $modelsWhereLatestIsUpdated->first()->id);
    }

    public function test_where_latest_event_is_with_multiple_matches(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);
        $model3 = TestModel::create(['name' => 'Model 3']);

        Carbon::setTestNow('2024-01-01 10:00:00');
        $model1->addEvent(TestEvent::Created);
        $model2->addEvent(TestEvent::Updated);
        $model3->addEvent(TestEvent::Created);

        Carbon::setTestNow('2024-01-01 12:00:00');
        $model1->addEvent(TestEvent::Viewed);
        $model3->addEvent(TestEvent::Viewed);

        // Models 1 and 3 have Viewed as their latest event
        $modelsWhereLatestIsViewed = TestModel::whereLatestEventIs(TestEvent::Viewed)->get();

        $this->assertCount(2, $modelsWhereLatestIsViewed);
        $this->assertTrue($modelsWhereLatestIsViewed->contains('id', $model1->id));
        $this->assertTrue($modelsWhereLatestIsViewed->contains('id', $model3->id));
    }

    public function test_where_latest_event_is_excludes_models_without_events(): void
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        $model1->addEvent(TestEvent::Created);
        // Model 2 has no events

        $modelsWhereLatestIsCreated = TestModel::whereLatestEventIs(TestEvent::Created)->get();

        $this->assertCount(1, $modelsWhereLatestIsCreated);
        $this->assertEquals($model1->id, $modelsWhereLatestIsCreated->first()->id);
    }

    public function test_where_latest_event_is_single_event(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $model->addEvent(TestEvent::Exported);

        $models = TestModel::whereLatestEventIs(TestEvent::Exported)->get();

        $this->assertCount(1, $models);
        $this->assertEquals($model->id, $models->first()->id);
    }
}
