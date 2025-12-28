<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Concerns\Eventable;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use Eventable;

    protected $table = 'test_models';

    protected $guarded = [];
}
