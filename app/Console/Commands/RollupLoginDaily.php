<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RollupLoginDaily extends Command
{
    protected $signature = 'analytics:rollup-login-daily {--date=}';
    protected $description = 'Aggregate login_events into login_daily per user and organization';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->subDay()->toDateString();

        // Aggregate counts for the specified date from login_events
        $this->info("Aggregating login events for {$date}...");

        DB::transaction(function () use ($date) {
            $sub = DB::table('login_events')
                ->select('user_id', 'organization_id', DB::raw('DATE(logged_in_at) as d'), DB::raw('COUNT(*) as c'))
                ->whereDate('logged_in_at', $date)
                ->groupBy('user_id', 'organization_id', 'd');

            // Upsert into login_daily
            $rows = $sub->get();
            if ($rows->isEmpty()) {
                return;
            }
            $payload = [];
            $now = now();
            foreach ($rows as $r) {
                $payload[] = [
                    'user_id' => $r->user_id,
                    'organization_id' => $r->organization_id,
                    'date' => $r->d,
                    'count' => $r->c,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('login_daily')->upsert(
                $payload,
                ['user_id', 'organization_id', 'date'],
                ['count', 'updated_at']
            );
        });

        $this->info('Done.');
        return self::SUCCESS;
    }
}

