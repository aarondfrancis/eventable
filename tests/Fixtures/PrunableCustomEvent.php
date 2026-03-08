<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Models\Event;

class PrunableCustomEvent extends Event
{
    public static bool $newQueryWasCalled = false;

    public function newQuery()
    {
        static::$newQueryWasCalled = true;

        return parent::newQuery();
    }

    public static function resetQueryFlag(): void
    {
        static::$newQueryWasCalled = false;
    }
}
