<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Rows inside a case are authorised through the case policy: whoever may view the case
 * sees them, whoever may edit the case edits them, and removing them follows
 * CourtCasePolicy::removeChildren(). Editing happens inside the workspace (view page).
 */
trait AuthorizesThroughCase
{
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord);
    }

    public function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        $case = $this->getOwnerRecord();

        $allowed = match ($action) {
            'viewAny', 'view' => Gate::allows('view', $case),
            'create', 'update', 'associate', 'attach' => Gate::allows('update', $case),
            'delete', 'deleteAny', 'detach', 'detachAny', 'dissociate', 'dissociateAny' => Gate::allows('removeChildren', $case),
            default => false,
        };

        return $allowed ? Response::allow() : Response::deny();
    }
}
