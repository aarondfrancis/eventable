<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Events Table
    |--------------------------------------------------------------------------
    |
    | The name of the database table used to store events.
    |
    */
    'table' => 'events',

    /*
    |--------------------------------------------------------------------------
    | Event Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used to store events. You can extend the default
    | Event model and specify your custom class here.
    |
    */
    'model' => AaronFrancis\Eventable\Models\Event::class,

    /*
    |--------------------------------------------------------------------------
    | Event Enum
    |--------------------------------------------------------------------------
    |
    | The enum class that defines your event types. This enum must be a
    | BackedEnum and should implement PruneableEvent if you want to use
    | the pruning functionality.
    |
    | Example:
    |   'event_enum' => App\Enums\EventType::class,
    |
    */
    'event_enum' => null,

    /*
    |--------------------------------------------------------------------------
    | Morph Map Registration
    |--------------------------------------------------------------------------
    |
    | Whether to automatically register the Event model in Laravel's morph map.
    | This helps keep your database clean by storing short type names instead
    | of full class names.
    |
    */
    'register_morph_map' => true,

    /*
    |--------------------------------------------------------------------------
    | Morph Alias
    |--------------------------------------------------------------------------
    |
    | The alias used in the morph map for the Event model.
    |
    */
    'morph_alias' => 'event',
];
