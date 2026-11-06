<?php

namespace Tests;

use App\Support\CaseAccess;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase runs `migrate:fresh --seed` once per run with this seeder;
     * every test then runs inside a transaction on top of it.
     */
    protected bool $seed = true;

    protected string $seeder = TestingSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        CaseAccess::flush();
    }
}
