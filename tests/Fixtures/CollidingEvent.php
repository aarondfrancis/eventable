<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

enum CollidingEvent: int
{
    case Created = 1;
    case Archived = 2;
}
