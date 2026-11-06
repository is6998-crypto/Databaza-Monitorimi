<?php

use App\Enums\StageOutcome;
use App\Enums\StageType;

it('computes transitive predecessors', function () {
    $predecessors = StageType::AppealDecision->allPredecessors();

    expect($predecessors)->toContain(StageType::AppealFiled, StageType::JudgmentAnnounced, StageType::MainHearing, StageType::IndictmentFiled)
        ->and($predecessors)->not->toContain(StageType::SupremeCourtDecision, StageType::AppealDecision);
});

it('has no cycles in the stage order', function () {
    foreach (StageType::cases() as $stage) {
        expect($stage->allPredecessors())->not->toContain($stage);
    }
});

it('limits outcomes to the stage', function () {
    expect(StageType::IndictmentDecision->outcomes())->toBe([StageOutcome::Approved, StageOutcome::Rejected, StageOutcome::Dismissed])
        ->and(StageType::JudgmentAnnounced->outcomes())->toContain(StageOutcome::Convicting, StageOutcome::Acquitting)
        ->and(StageType::InvestigationOpened->hasOutcome())->toBeFalse();
});
