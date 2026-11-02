<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Database\Faker\KosovoProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $kosovo = new KosovoProvider($this->faker);

        return [
            'name' => $kosovo->kosovoName(),
            'email' => $this->faker->unique()->userName().'@example.test',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'locale' => 'sq',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * A user that already completed 2FA setup (TOTP secret present).
     */
    public function withMfa(): static
    {
        return $this->state(fn (array $attributes) => ['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    }

    public function role(Role $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }
}
