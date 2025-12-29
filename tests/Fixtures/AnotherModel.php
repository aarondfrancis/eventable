<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Model;

class AnotherModel extends Model
{
    use HasEvents;

    protected $guarded = [];
}
