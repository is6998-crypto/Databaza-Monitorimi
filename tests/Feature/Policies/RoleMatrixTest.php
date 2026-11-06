<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;

it('creates all five roles from the proposal', function () {
    foreach (Role::cases() as $role) {
        expect(Spatie\Permission\Models\Role::query()->where('name', $role->value)->exists())->toBeTrue();
    }
});

it('grants the editor review, publishing and reference-data rights but not deletion', function () {
    $editor = userWithRole(Role::Editor);

    expect($editor->checkPermissionTo(Permissions::REVIEW))->toBeTrue()
        ->and($editor->checkPermissionTo(Permissions::PUBLISH))->toBeTrue()
        ->and($editor->checkPermissionTo('Update:Court'))->toBeTrue()
        ->and($editor->checkPermissionTo(Permissions::MERGE_REFERENCE))->toBeTrue()
        ->and($editor->checkPermissionTo('Delete:CourtCase'))->toBeFalse()
        ->and($editor->checkPermissionTo('ViewAny:User'))->toBeFalse();
});

it('lets monitors create cases and hearings but never delete', function () {
    $monitor = userWithRole(Role::Monitor);

    expect($monitor->checkPermissionTo('Create:CourtCase'))->toBeTrue()
        ->and($monitor->checkPermissionTo('Create:Hearing'))->toBeTrue()
        ->and($monitor->checkPermissionTo('Delete:CourtCase'))->toBeFalse()
        ->and($monitor->checkPermissionTo('Delete:Hearing'))->toBeFalse()
        ->and($monitor->checkPermissionTo(Permissions::REVIEW))->toBeFalse()
        ->and($monitor->checkPermissionTo(Permissions::VIEW_ANALYTICS))->toBeFalse();
});

it('keeps analysts read-only with dashboards and exports', function () {
    $analyst = userWithRole(Role::Analyst);

    expect($analyst->checkPermissionTo('ViewAny:CourtCase'))->toBeTrue()
        ->and($analyst->checkPermissionTo('View:Hearing'))->toBeTrue()
        ->and($analyst->checkPermissionTo(Permissions::EXPORT_DATA))->toBeTrue()
        ->and($analyst->checkPermissionTo(Permissions::VIEW_ANALYTICS))->toBeTrue()
        ->and($analyst->checkPermissionTo('Create:CourtCase'))->toBeFalse()
        ->and($analyst->checkPermissionTo('Update:Hearing'))->toBeFalse();
});

it('gives researchers no staff permissions', function () {
    $researcher = userWithRole(Role::Researcher);

    expect($researcher->getAllPermissions())->toBeEmpty();
});

it('lets only the super admin manage users and reset 2FA', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $editor = userWithRole(Role::Editor);
    $target = User::factory()->withMfa()->create();

    expect(Gate::forUser($admin)->allows('viewAny', User::class))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('viewAny', User::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('resetMultiFactor', $target))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('resetMultiFactor', $target))->toBeFalse();
});

it('only allows the super admin into user management screens', function () {
    $this->actingAs(userWithRole(Role::Editor))->get('/admin/users')->assertForbidden();
    $this->actingAs(userWithRole(Role::SuperAdmin))->get('/admin/users')->assertOk();
});

it('only allows the super admin to read the audit log', function () {
    $this->actingAs(userWithRole(Role::Analyst))->get('/admin/audit-log')->assertForbidden();
    $this->actingAs(userWithRole(Role::SuperAdmin))->get('/admin/audit-log')->assertOk();
});
