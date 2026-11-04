<?php

use App\Enums\ResearcherStatus;
use App\Enums\Role;
use App\Filament\Admin\Resources\Researchers\Pages\ListResearcherProfiles;
use App\Filament\Admin\Resources\Researchers\Pages\ViewResearcherProfile;
use App\Filament\Admin\Resources\Researchers\ResearcherProfileResource;
use App\Filament\Research\Pages\ApiTokens;
use App\Filament\Research\Pages\Auth\Register;
use App\Filament\Research\Pages\Datasets;
use App\Filament\Research\Pages\DataUseAgreement;
use App\Models\ResearcherProfile;
use App\Models\User;
use App\Notifications\ResearcherRegistered;
use App\Notifications\ResearcherReviewed;
use App\Services\Research\ResearcherReview;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/*
| Proposal B6.8: researchers register, an admin approves them, they accept a data-use
| agreement, and only then get anonymized exports and API tokens.
*/

it('shows the researcher login and registration pages', function () {
    $this->get('/research/login')->assertOk();
    $this->get('/research/register')->assertOk()->assertSee(__('research.register.institution'));
});

it('registers a researcher as pending and tells the administrators', function () {
    Notification::fake();
    Filament::setCurrentPanel(Filament::getPanel('research'));
    $admin = userWithRole(Role::SuperAdmin);
    $editor = userWithRole(Role::Editor);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Researcher Test',
            'email' => 'new.researcher@example.org',
            'institution' => 'Institute for Testing',
            'position' => 'Fellow',
            'research_purpose' => 'A study of trial delays in corruption cases across Kosovo courts.',
            'password' => 'a-Long-password-123',
            'passwordConfirmation' => 'a-Long-password-123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'new.researcher@example.org')->firstOrFail();

    expect($user->hasRole(Role::Researcher->value))->toBeTrue()
        ->and($user->isStaff())->toBeFalse()
        ->and($user->researcherProfile?->status)->toBe(ResearcherStatus::Pending)
        ->and($user->researcherProfile?->institution)->toBe('Institute for Testing')
        ->and($user->isApprovedResearcher())->toBeFalse();

    Notification::assertSentTo($admin, ResearcherRegistered::class);
    Notification::assertNotSentTo($editor, ResearcherRegistered::class);
});

it('requires an institution and a meaningful research purpose', function () {
    Filament::setCurrentPanel(Filament::getPanel('research'));

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Researcher Test',
            'email' => 'short@example.org',
            'institution' => '',
            'research_purpose' => 'Too short',
            'password' => 'a-Long-password-123',
            'passwordConfirmation' => 'a-Long-password-123',
        ])
        ->call('register')
        ->assertHasFormErrors(['institution' => 'required', 'research_purpose' => 'min']);

    expect(User::query()->where('email', 'short@example.org')->exists())->toBeFalse();
});

it('shows the approval status on the researcher home page', function (ResearcherStatus $status, bool $agreement, string $expected) {
    $this->actingAs(researcher($status, $agreement));

    $this->get('/research')
        ->assertOk()
        ->assertSee('data-research-status="'.$status->value.'"', false)
        ->assertSee($expected);
})->with([
    'pending' => [ResearcherStatus::Pending, false, fn () => __('research.dashboard.pending_title')],
    'approved, agreement missing' => [ResearcherStatus::Approved, false, fn () => __('research.dashboard.agreement_title')],
    'approved' => [ResearcherStatus::Approved, true, fn () => __('research.dashboard.approved_title')],
    'rejected' => [ResearcherStatus::Rejected, false, fn () => __('research.dashboard.rejected_title')],
    'suspended' => [ResearcherStatus::Suspended, true, fn () => __('research.dashboard.rejected_title')],
]);

it('keeps datasets and API tokens closed until approval and the agreement', function (ResearcherStatus $status, bool $agreement, int $code) {
    $this->actingAs(researcher($status, $agreement));

    $this->get(Datasets::getUrl(panel: 'research'))->assertStatus($code);
    $this->get(ApiTokens::getUrl(panel: 'research'))->assertStatus($code);
})->with([
    'pending' => [ResearcherStatus::Pending, false, 403],
    'approved, agreement missing' => [ResearcherStatus::Approved, false, 403],
    'rejected' => [ResearcherStatus::Rejected, false, 403],
    'suspended' => [ResearcherStatus::Suspended, true, 403],
    'approved with agreement' => [ResearcherStatus::Approved, true, 200],
]);

it('keeps staff out of the researcher area', function () {
    $this->actingAs(userWithRole(Role::SuperAdmin));

    $this->get('/research')->assertForbidden();
});

it('keeps deactivated researchers out', function () {
    $user = researcher();
    $user->update(['is_active' => false]);

    $this->actingAs($user->fresh())->get('/research')->assertForbidden();
    expect($user->fresh()->isApprovedResearcher())->toBeFalse();
});

it('records the acceptance of the data-use agreement', function () {
    Filament::setCurrentPanel(Filament::getPanel('research'));
    $user = researcher(ResearcherStatus::Approved, agreement: false);
    $this->actingAs($user);

    $this->get(DataUseAgreement::getUrl(panel: 'research'))->assertOk()->assertSee(__('research.agreement.clauses')[0]);

    Livewire::test(DataUseAgreement::class)
        ->callAction('accept', data: ['confirm' => false])
        ->assertHasActionErrors(['confirm' => 'accepted']);

    expect($user->fresh()->isApprovedResearcher())->toBeFalse();

    Livewire::test(DataUseAgreement::class)
        ->callAction('accept', data: ['confirm' => true])
        ->assertHasNoActionErrors()
        ->assertRedirect(Datasets::getUrl(panel: 'research'));

    $profile = $user->fresh()->researcherProfile;
    expect($profile->agreement_version)->toBe(config('courtwatch.data_use_agreement_version'))
        ->and($profile->agreement_accepted_at)->not->toBeNull()
        ->and($user->fresh()->isApprovedResearcher())->toBeTrue();
});

it('asks for the agreement again when its version changes', function () {
    $user = researcher();
    expect($user->isApprovedResearcher())->toBeTrue();

    config(['courtwatch.data_use_agreement_version' => '2099-1']);

    expect($user->fresh()->isApprovedResearcher())->toBeFalse();
    $this->actingAs($user->fresh())->get(Datasets::getUrl(panel: 'research'))->assertForbidden();
});

it('lets an administrator approve a pending researcher', function () {
    Notification::fake();
    $admin = actingAsRole(Role::SuperAdmin);
    $pending = researcher(ResearcherStatus::Pending);
    $profile = $pending->researcherProfile;

    Livewire::test(ListResearcherProfiles::class)
        ->assertCanSeeTableRecords([$profile])
        ->assertActionHidden(TestAction::make('suspend')->table($profile))
        ->callAction(TestAction::make('approve')->table($profile), data: ['note' => 'Welcome'])
        ->assertHasNoActionErrors();

    $profile->refresh();
    expect($profile->status)->toBe(ResearcherStatus::Approved)
        ->and($profile->reviewed_by)->toBe($admin->id)
        ->and($profile->reviewed_at)->not->toBeNull()
        ->and($profile->review_note)->toBe('Welcome');

    Notification::assertSentTo($pending, ResearcherReviewed::class);
    expect(Activity::query()->where('log_name', 'researchers')->where('event', 'approved')->where('causer_id', $admin->id)->exists())->toBeTrue();

    // Approved, but the agreement is still missing.
    expect($pending->fresh()->isApprovedResearcher())->toBeFalse();
});

it('requires a reason to reject or suspend a researcher', function () {
    Notification::fake();
    actingAsRole(Role::SuperAdmin);
    $profile = researcher(ResearcherStatus::Pending)->researcherProfile;

    Livewire::test(ViewResearcherProfile::class, ['record' => $profile->getRouteKey()])
        ->callAction('reject', data: ['note' => ''])
        ->assertHasActionErrors(['note' => 'required']);

    expect($profile->fresh()->status)->toBe(ResearcherStatus::Pending);

    Livewire::test(ViewResearcherProfile::class, ['record' => $profile->getRouteKey()])
        ->callAction('reject', data: ['note' => 'The purpose is not research.'])
        ->assertHasNoActionErrors();

    expect($profile->fresh()->status)->toBe(ResearcherStatus::Rejected);
    Notification::assertSentTo($profile->user, ResearcherReviewed::class);
});

it('revokes every API token when a researcher is suspended', function () {
    Notification::fake();
    actingAsRole(Role::SuperAdmin);
    $user = researcher();
    $user->createToken('analysis', ['research:read']);
    $user->createToken('backup', ['research:read']);

    Livewire::test(ViewResearcherProfile::class, ['record' => $user->researcherProfile->getRouteKey()])
        ->callAction('suspend', data: ['note' => 'Data shared with third parties.'])
        ->assertHasNoActionErrors();

    expect($user->fresh()->researcherProfile->status)->toBe(ResearcherStatus::Suspended)
        ->and($user->tokens()->count())->toBe(0)
        ->and($user->fresh()->isApprovedResearcher())->toBeFalse();
});

it('only lets administrators review researchers', function (Role $role) {
    $this->actingAs(userWithRole($role));
    $profile = researcher(ResearcherStatus::Pending)->researcherProfile;

    $this->get('/admin/researchers')->assertForbidden();

    expect(fn () => app(ResearcherReview::class)->decide($profile, ResearcherStatus::Approved, auth()->user()))
        ->toThrow(AuthorizationException::class);

    expect($profile->fresh()->status)->toBe(ResearcherStatus::Pending);
})->with([Role::Editor, Role::Monitor, Role::Analyst]);

it('shows the number of pending researchers in the navigation', function () {
    researcher(ResearcherStatus::Pending);
    researcher(ResearcherStatus::Pending);
    researcher();

    expect(ResearcherProfile::query()->count())->toBe(3);
    expect(ResearcherProfileResource::getNavigationBadge())->toBe('2');
});
