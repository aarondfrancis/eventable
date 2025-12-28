<?php

namespace AaronFrancis\Eventable\Commands;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\Models\Event;
use AaronFrancis\Eventable\PruneableEventDiscovery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneEventsCommand extends Command
{
    protected $signature = 'eventable:prune {--dry-run}';

    protected $description = 'Prune old events based on configured retention policies';

    public function handle(): int
    {
        $enumClasses = PruneableEventDiscovery::discover();

        if (empty($enumClasses)) {
            $this->error('No PruneableEvent enums found. Create an enum implementing PruneableEvent in your app directory.');

            return self::FAILURE;
        }

        $eventModel = config('eventable.model', Event::class);
        $table = (new $eventModel)->getTable();

        $pruned = 0;

        foreach ($enumClasses as $enumClass) {
            if (! enum_exists($enumClass)) {
                $this->warn("Enum [{$enumClass}] does not exist, skipping.");

                continue;
            }

            foreach ($enumClass::cases() as $case) {
                if (! $case instanceof PruneableEvent) {
                    continue;
                }

                $prune = $case->prune();

                if (is_null($prune)) {
                    continue;
                }

                $query = DB::table($table)->where('type', $case->value);

                if ($prune->keep) {
                    $partitionBy = ['eventable_id', 'eventable_type'];

                    if ($prune->varyOnData) {
                        $partitionBy[] = 'data';
                    }

                    $partitionBy = implode(', ', $partitionBy);

                    $ranked = DB::table($table)
                        // Limit to only the enum we're currently working on.
                        ->where('type', $case->value)
                        // We use this to exclude models further down.
                        ->select('id')
                        // Partition by model (eventable_id + eventable_type) and order by created_at such
                        // that have a ranked list of events of this type that were added to this model.
                        ->selectRaw("row_number() over (partition by $partitionBy order by created_at desc) as num");

                    $query
                        // Add in our CTE from above.
                        ->withExpression('ranked', $ranked)
                        ->whereNotIn('id', function ($sub) use ($prune) {
                            // Keep the top N models, based on the CTE.
                            $sub->from('ranked')->select('id')->where('num', '<=', $prune->keep);
                        });
                }

                if ($prune->before) {
                    $query->where('created_at', '<', $prune->before);
                }

                if ($this->option('dry-run')) {
                    $count = $query->count();
                    $this->line("Event {$case->name}: ".number_format($count).' records to prune.');
                    $pruned += $count;
                } else {
                    $deleted = $query->delete();
                    $this->line("Event {$case->name}: ".number_format($deleted).' records pruned.');
                    $pruned += $deleted;
                }
            }
        }

        $action = $this->option('dry-run') ? 'would be pruned' : 'pruned';
        $this->info('Total: '.number_format($pruned)." records {$action}.");

        return self::SUCCESS;
    }
}
