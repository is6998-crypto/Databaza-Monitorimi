<?php

namespace Database\Seeders;

use App\Enums\ResearcherStatus;
use App\Enums\Role;
use App\Models\Reference\Court;
use App\Models\Reference\Monitor;
use App\Models\ResearcherProfile;
use App\Models\User;
use Database\Faker\KosovoProvider;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts for each role (development only, never in production).
 * All names are fake. Password: DEMO_PASSWORD (default "password").
 */
class DemoUsersSeeder extends Seeder
{
    /** @var list<array{email: string, role: Role, locale: string}> */
    public const ACCOUNTS = [
        ['email' => 'admin@courtwatch.test', 'role' => Role::SuperAdmin, 'locale' => 'sq'],
        ['email' => 'editor@courtwatch.test', 'role' => Role::Editor, 'locale' => 'sq'],
        ['email' => 'monitor@courtwatch.test', 'role' => Role::Monitor, 'locale' => 'sq'],
        ['email' => 'monitor2@courtwatch.test', 'role' => Role::Monitor, 'locale' => 'en'],
        ['email' => 'analyst@courtwatch.test', 'role' => Role::Analyst, 'locale' => 'en'],
        ['email' => 'researcher@courtwatch.test', 'role' => Role::Researcher, 'locale' => 'en'],
        ['email' => 'researcher.pending@courtwatch.test', 'role' => Role::Researcher, 'locale' => 'en'],
    ];

    public function run(): void
    {
        $faker = FakerFactory::create();
        $faker->seed(2026);
        $kosovo = new KosovoProvider($faker);

        $password = Hash::make((string) config('courtwatch.demo_password', 'password'));

        foreach (self::ACCOUNTS as $index => $account) {
            $user = User::query()->firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $kosovo->kosovoName(),
                    'password' => $password,
                    'locale' => $account['locale'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$account['role']->value]);

            if ($account['role'] === Role::Monitor) {
                $monitor = Monitor::query()->firstOrCreate(['user_id' => $user->id], ['code' => sprintf('M-%02d', $index), 'is_active' => true]);
                $courts = $account['email'] === 'monitor@courtwatch.test'
                    ? Court::query()->whereIn('code', ['GJTH-PR', 'GJTH-PZ'])->pluck('id')
                    : Court::query()->whereIn('code', ['GJTH-GJ'])->pluck('id');
                $monitor->courts()->sync($courts);
            }

            if ($account['role'] === Role::Researcher) {
                $approved = $account['email'] === 'researcher@courtwatch.test';

                ResearcherProfile::query()->updateOrCreate(['user_id' => $user->id], [
                    'institution' => $approved ? 'University of Prishtina (demo)' : 'Demo Research Institute',
                    'position' => 'PhD candidate',
                    'research_purpose' => 'Demo account: analysis of trial delays in corruption cases.',
                    'status' => $approved ? ResearcherStatus::Approved : ResearcherStatus::Pending,
                    'reviewed_at' => $approved ? now() : null,
                    'agreement_version' => $approved ? config('courtwatch.data_use_agreement_version') : null,
                    'agreement_accepted_at' => $approved ? now() : null,
                ]);
            }
        }

        // A few extra (fake) monitors so assignments and analytics look realistic.
        foreach (range(1, 6) as $i) {
            $user = User::query()->firstOrCreate(
                ['email' => "monitor{$i}.demo@courtwatch.test"],
                ['name' => $kosovo->kosovoName(), 'password' => $password, 'locale' => 'sq', 'is_active' => true, 'email_verified_at' => now()],
            );
            $user->syncRoles([Role::Monitor->value]);
            $monitor = Monitor::query()->firstOrCreate(['user_id' => $user->id], ['code' => sprintf('M-%02d', 10 + $i), 'is_active' => true]);
            $monitor->courts()->sync(Court::query()->where('level', 'basic')->inRandomOrder()->limit(2)->pluck('id'));
        }
    }
}
