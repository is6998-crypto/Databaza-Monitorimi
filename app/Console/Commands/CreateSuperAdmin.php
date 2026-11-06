<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first Super Admin on a new installation (production seeds no demo accounts).
 * Two-factor authentication is set up at the first sign-in, as for every staff account.
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-admin
        {email : E-mail address used to sign in}
        {--name= : Full name}
        {--locale=sq : Interface language (sq or en)}';

    protected $description = 'Create a Super Admin account (2FA is set up at the first sign-in)';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = trim((string) ($this->option('name') ?: $this->ask('Full name')));
        $locale = (string) $this->option('locale');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'locale' => $locale],
            [
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'name' => ['required', 'string', 'max:255'],
                'locale' => ['required', Rule::in((array) config('courtwatch.supported_locales'))],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (at least 12 characters, upper and lower case letters and numbers)');

        if ($password !== (string) $this->secret('Repeat the password')) {
            $this->error('The passwords do not match.');

            return self::FAILURE;
        }

        $passwordCheck = Validator::make(['password' => $password], ['password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()]]);

        if ($passwordCheck->fails()) {
            foreach ($passwordCheck->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'locale' => $locale,
            'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole(Role::SuperAdmin->value);

        $this->info("Super Admin {$email} created. Sign in at ".url('/admin').' and set up two-factor authentication.');

        return self::SUCCESS;
    }
}
