<?php

namespace AaronFrancis\Eventable;

use AaronFrancis\Eventable\Commands\PruneEventsCommand;
use AaronFrancis\Eventable\Models\Event;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EventableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eventable.php', 'eventable');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/eventable.php' => config_path('eventable.php'),
            ], 'eventable-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_events_table.php.stub' => $this->getMigrationFileName(),
            ], 'eventable-migrations');

            $this->commands([
                PruneEventsCommand::class,
            ]);
        }

        $this->registerMorphMap();
    }

    protected function registerMorphMap(): void
    {
        if (! config('eventable.register_morph_map', true)) {
            return;
        }

        $eventModel = config('eventable.model', Event::class);
        $morphAlias = config('eventable.morph_alias', 'event');

        Relation::morphMap([
            $morphAlias => $eventModel,
        ]);
    }

    protected function getMigrationFileName(): string
    {
        $timestamp = date('Y_m_d_His');

        return database_path("migrations/{$timestamp}_create_events_table.php");
    }
}
