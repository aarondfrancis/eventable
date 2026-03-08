<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Model;

class StringKeyTestModel extends Model
{
    use HasEvents;

    protected $table = 'string_key_test_models';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';
}
