<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

enum TestEvent: int
{
    case Created = 1;
    case Updated = 2;
    case Deleted = 3;
    case Viewed = 4;
    case Exported = 5;
}
