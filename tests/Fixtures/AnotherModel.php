<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Concerns\Eventable;
use Illuminate\Database\Eloquent\Model;

class AnotherModel extends Model
{
    use Eventable;

    protected $guarded = [];
}
