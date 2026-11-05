<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * GET /up (proposal B6.10): besides booting the application, check that the database and
 * the cache (Redis) answer. An exception makes /up return HTTP 500, which the Docker
 * health check and uptime monitors pick up.
 */
class CheckApplicationHealth
{
    public function handle(DiagnosingHealth $event): void
    {
        DB::connection()->select('select 1');

        Cache::store()->put('health-check', now()->timestamp, 60);
        Cache::store()->get('health-check');
    }
}
