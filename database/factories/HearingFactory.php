<?php

namespace Database\Factories;

use App\Enums\HearingType;
use App\Enums\Participant;
use App\Enums\PostponementReason;
use App\Enums\Recording;
use App\Enums\Venue;
use App\Models\CourtCase;
use App\Models\Hearing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hearing>
 */
class HearingFactory extends Factory
{
    protected $model = Hearing::class;

    public function definition(): array
    {
        $postponed = $this->faker->boolean(35);
        $late = $this->faker->boolean(30);
        $interpretation = $this->faker->boolean(15);

        return [
            'case_id' => CourtCase::factory(),
            'hearing_date' => $this->faker->dateTimeBetween('-3 years', '-1 day')->format('Y-m-d'),
            'hearing_type' => $this->faker->randomElement(HearingType::cases()),
            'venue' => $this->faker->randomElement([Venue::Courtroom, Venue::Courtroom, Venue::Courtroom, Venue::Office]),
            'is_public' => $this->faker->boolean(85),
            'started_late' => $late,
            'delay_minutes' => $late ? $this->faker->numberBetween(16, 120) : null,
            'delay_cause' => $late ? $this->faker->randomElement(['panel', 'prosecutor', 'defence', 'other']) : null,
            'was_postponed' => $postponed,
            'postponement_reasons' => $postponed ? $this->faker->randomElements(PostponementReason::values(), $this->faker->numberBetween(1, 2)) : null,
            'phone_used' => false,
            'security_adequate' => $this->faker->boolean(90),
            'recording' => $this->faker->randomElement(Recording::cases()),
            'interpretation_needed' => $interpretation,
            'interpretation_provided' => $interpretation ? $this->faker->boolean(80) : null,
            'participants' => $this->faker->randomElements(Participant::values(), 4),
        ];
    }

    public function forCase(CourtCase $case): static
    {
        return $this->state(fn () => [
            'case_id' => $case->id,
            'court_id' => $case->court_id,
            'judge_id' => $case->judge_id,
            'prosecutor_id' => $case->prosecutor_id,
        ]);
    }
}
