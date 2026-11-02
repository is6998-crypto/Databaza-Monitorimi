<?php

use App\Enums\WorkflowStatus;

it('follows draft → submitted → in review → approved → published', function () {
    expect(WorkflowStatus::Draft->canTransitionTo(WorkflowStatus::Submitted))->toBeTrue()
        ->and(WorkflowStatus::Submitted->canTransitionTo(WorkflowStatus::InReview))->toBeTrue()
        ->and(WorkflowStatus::InReview->canTransitionTo(WorkflowStatus::Approved))->toBeTrue()
        ->and(WorkflowStatus::Approved->canTransitionTo(WorkflowStatus::Published))->toBeTrue();
});

it('allows returning submissions with a comment and resubmitting', function () {
    expect(WorkflowStatus::InReview->canTransitionTo(WorkflowStatus::Returned))->toBeTrue()
        ->and(WorkflowStatus::Returned->canTransitionTo(WorkflowStatus::Submitted))->toBeTrue();
});

it('refuses to skip review', function () {
    expect(WorkflowStatus::Draft->canTransitionTo(WorkflowStatus::Approved))->toBeFalse()
        ->and(WorkflowStatus::Draft->canTransitionTo(WorkflowStatus::Published))->toBeFalse()
        ->and(WorkflowStatus::Returned->canTransitionTo(WorkflowStatus::Approved))->toBeFalse();
});

it('only treats approved and published data as verified', function () {
    expect(array_map(fn (WorkflowStatus $s) => $s->isVerified(), WorkflowStatus::cases()))
        ->toBe([false, false, false, true, true, false]);
});
