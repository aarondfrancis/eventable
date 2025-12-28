<?php

use AaronFrancis\Eventable\Tests\Fixtures\TestEvent;
use AaronFrancis\Eventable\Tests\Fixtures\TestModel;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

/*
|--------------------------------------------------------------------------
| whereEventHasHappenedTimes() Tests
|--------------------------------------------------------------------------
*/

it('whereEventHasHappenedTimes exact match', function () {
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

    expect($modelsWithExactly3)->toHaveCount(2);
    expect($modelsWithExactly3->contains('id', $model1->id))->toBeTrue();
    expect($modelsWithExactly3->contains('id', $model3->id))->toBeTrue();
});

it('whereEventHasHappenedTimes zero', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->addEvent(TestEvent::Viewed);

    $modelsWithZero = TestModel::whereEventHasHappenedTimes(TestEvent::Viewed, 0)->get();

    expect($modelsWithZero)->toHaveCount(1);
    expect($modelsWithZero->first()->id)->toBe($model2->id);
});

it('whereEventHasHappenedTimes with data', function () {
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

    expect($modelsWithExactly2NameUpdates)->toHaveCount(1);
    expect($modelsWithExactly2NameUpdates->first()->id)->toBe($model1->id);
});

/*
|--------------------------------------------------------------------------
| whereEventHasHappenedAtLeast() Tests
|--------------------------------------------------------------------------
*/

it('whereEventHasHappenedAtLeast', function () {
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

    expect($modelsWithAtLeast3)->toHaveCount(2);
    expect($modelsWithAtLeast3->contains('id', $model1->id))->toBeTrue();
    expect($modelsWithAtLeast3->contains('id', $model3->id))->toBeTrue();
});

it('whereEventHasHappenedAtLeast one', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);
    $model3 = TestModel::create(['name' => 'Model 3']);

    $model1->addEvent(TestEvent::Exported);
    $model3->addEvent(TestEvent::Exported);
    $model3->addEvent(TestEvent::Exported);

    $modelsWithAtLeast1 = TestModel::whereEventHasHappenedAtLeast(TestEvent::Exported, 1)->get();

    expect($modelsWithAtLeast1)->toHaveCount(2);
    expect($modelsWithAtLeast1->contains('id', $model1->id))->toBeTrue();
    expect($modelsWithAtLeast1->contains('id', $model3->id))->toBeTrue();
});

it('whereEventHasHappenedAtLeast with data', function () {
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

    expect($modelsWithAtLeast2UsdOrders)->toHaveCount(1);
    expect($modelsWithAtLeast2UsdOrders->first()->id)->toBe($model1->id);
});

/*
|--------------------------------------------------------------------------
| whereLatestEventIs() Tests
|--------------------------------------------------------------------------
*/

it('whereLatestEventIs', function () {
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

    expect($modelsWhereLatestIsUpdated)->toHaveCount(1);
    expect($modelsWhereLatestIsUpdated->first()->id)->toBe($model1->id);
});

it('whereLatestEventIs with multiple matches', function () {
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

    $modelsWhereLatestIsViewed = TestModel::whereLatestEventIs(TestEvent::Viewed)->get();

    expect($modelsWhereLatestIsViewed)->toHaveCount(2);
    expect($modelsWhereLatestIsViewed->contains('id', $model1->id))->toBeTrue();
    expect($modelsWhereLatestIsViewed->contains('id', $model3->id))->toBeTrue();
});

it('whereLatestEventIs excludes models without events', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->addEvent(TestEvent::Created);
    // Model 2 has no events

    $modelsWhereLatestIsCreated = TestModel::whereLatestEventIs(TestEvent::Created)->get();

    expect($modelsWhereLatestIsCreated)->toHaveCount(1);
    expect($modelsWhereLatestIsCreated->first()->id)->toBe($model1->id);
});

it('whereLatestEventIs single event', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->addEvent(TestEvent::Exported);

    $models = TestModel::whereLatestEventIs(TestEvent::Exported)->get();

    expect($models)->toHaveCount(1);
    expect($models->first()->id)->toBe($model->id);
});
