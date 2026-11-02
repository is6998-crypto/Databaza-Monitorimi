<?php

namespace Database\Factories;

use App\Enums\CounselType;
use App\Enums\Gender;
use App\Enums\PartyRole;
use App\Models\CaseParty;
use App\Models\CourtCase;
use Database\Faker\KosovoProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseParty>
 */
class CasePartyFactory extends Factory
{
    protected $model = CaseParty::class;

    public function definition(): array
    {
        $kosovo = new KosovoProvider($this->faker);
        $gender = $this->faker->randomElement([Gender::Male, Gender::Male, Gender::Male, Gender::Female]);

        return [
            'case_id' => CourtCase::factory(),
            'role' => PartyRole::Accused,
            'name' => $kosovo->kosovoName($gender->value),
            'gender' => $gender,
            'counsel_type' => $this->faker->randomElement(CounselType::cases()),
            'counsel_at_police' => $this->faker->boolean(60),
        ];
    }

    public function role(PartyRole $role): static
    {
        return $this->state(function () use ($role) {
            if ($role === PartyRole::LegalEntity) {
                return ['role' => $role, 'gender' => Gender::LegalPerson, 'name' => (new KosovoProvider($this->faker))->kosovoCompany()];
            }

            return ['role' => $role];
        });
    }
}
