<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Models\Event;

class CustomEvent extends Event
{
    protected $appends = ['custom_attribute'];

    public function getCustomAttributeAttribute(): string
    {
        return 'custom_value';
    }
}
