<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

enum StringEvent: string
{
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserDeleted = 'user.deleted';
}
