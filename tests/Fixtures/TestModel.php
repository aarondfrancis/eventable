<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasEvents;

    protected $table = 'test_models';

    protected $guarded = [];
}
