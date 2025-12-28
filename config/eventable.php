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
    | Event Types Map
    |--------------------------------------------------------------------------
    |
    | Register your event enums here with short aliases. This is REQUIRED for
    | all event enums you use. The alias is stored in the database, allowing
    | you to refactor enum class names without breaking existing data.
    |
    | Example:
    |   'event_types' => [
    |       'user' => App\Enums\UserEvent::class,
    |       'order' => App\Enums\OrderEvent::class,
    |   ],
    |
    */
    'event_types' => [
        // 'user' => App\Enums\UserEvent::class,
    ],

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
