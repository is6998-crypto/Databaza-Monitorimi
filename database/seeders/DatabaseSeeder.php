<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Roles and the public court/criminal-code structure are always seeded.
     * Fake demo accounts and records only outside production (SEED_DEMO_DATA=true).
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ReferenceDataSeeder::class,
        ]);

        if (app()->isProduction() || ! config('courtwatch.seed_demo_data')) {
            return;
        }

        $this->call([
            DemoUsersSeeder::class,
            DemoReferenceSeeder::class,
            DemoCasesSeeder::class,
            DemoPublicationsSeeder::class,
        ]);
    }
}
