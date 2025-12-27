<?php

namespace AaronFrancis\Eventable\Commands;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneEventsCommand extends Command
{
    protected $signature = 'eventable:prune {--dry-run}';

    protected $description = 'Prune old events based on configured retention policies';

    public function handle(): int
    {
        $enumClass = config('eventable.event_enum', null);

        if (!$enumClass) {
            $this->error('No event enum configured. Set eventable.event_enum in your config.');
            return self::FAILURE;
        }

        if (!enum_exists($enumClass)) {
            $this->error("The configured event enum [{$enumClass}] does not exist.");
            return self::FAILURE;
        }

        $eventModel = config('eventable.model', Event::class);
        $table = (new $eventModel)->getTable();

        $pruned = 0;

        foreach ($enumClass::cases() as $case) {
            if (!$case instanceof PruneableEvent) {
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
                $this->line("Event {$case->name}: " . number_format($count) . ' records to prune.');
                $pruned += $count;
            } else {
                $deleted = $query->delete();
                $this->line("Event {$case->name}: " . number_format($deleted) . ' records pruned.');
                $pruned += $deleted;
            }
        }

        $action = $this->option('dry-run') ? 'would be pruned' : 'pruned';
        $this->info("Total: " . number_format($pruned) . " records {$action}.");

        return self::SUCCESS;
    }
}
