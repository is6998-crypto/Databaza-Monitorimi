<?php

namespace Database\Factories;

use App\Enums\CaseType;
use App\Enums\WorkflowStatus;
use App\Models\CourtCase;
use Database\Faker\KosovoProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourtCase>
 */
class CourtCaseFactory extends Factory
{
    protected $model = CourtCase::class;

    public function definition(): array
    {
        $kosovo = new KosovoProvider($this->faker);
        $court = RefHelper::court();
        $type = $this->faker->randomElement(CaseType::cases());
        $prosecutor = RefHelper::prosecutor();

        return [
            'type' => $type,
            'court_id' => $court->id,
            'judge_id' => RefHelper::judge($court)->id,
            'prosecutor_id' => $type->isCriminal() ? $prosecutor->id : null,
            'prosecution_office_id' => $type->isCriminal() ? $prosecutor->prosecution_office_id : null,
            'court_case_number' => $kosovo->courtCaseNumber($type->isCriminal() ? 'PKR' : ($type === CaseType::Administrative ? 'A' : 'C')),
            'indictment_number' => $type->isCriminal() ? $kosovo->indictmentNumber() : null,
            'police_case_number' => $type->isCriminal() ? $kosovo->policeCaseNumber() : null,
        ];
    }

    public function type(CaseType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function status(WorkflowStatus $status): static
    {
        return $this->afterMaking(function (CourtCase $case) use ($status): void {
            $case->status = $status;
        });
    }

    public function approved(): static
    {
        return $this->status(WorkflowStatus::Approved)->afterMaking(function (CourtCase $case): void {
            $case->approved_at = now();
        });
    }
}
