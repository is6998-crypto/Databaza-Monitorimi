<?php

namespace App\Filament\Admin\Actions;

use App\Enums\WorkflowStatus;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\User;
use App\Services\WorkflowService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Review-workflow actions shared by the case and hearing pages, the review queue and tables.
 * Visibility mirrors the policies; the WorkflowService checks them again on the server.
 */
final class WorkflowActions
{
    /**
     * @return list<Action|ActionGroup>
     */
    public static function all(): array
    {
        return [
            self::submit(),
            self::startReview(),
            self::approve(),
            self::returnForCorrection(),
            self::publish(),
            self::unpublish(),
            self::changes(),
        ];
    }

    public static function submit(): Action
    {
        return Action::make('submitForReview')
            ->label(__('workflow.actions.submit'))
            ->icon(WorkflowStatus::Submitted->getIcon())
            ->color('info')
            ->requiresConfirmation()
            ->modalDescription(__('workflow.actions.submit_help'))
            ->visible(fn (CourtCase|Hearing $record): bool => Gate::allows('submit', $record))
            ->action(self::run(fn (WorkflowService $workflow, CourtCase|Hearing $record, User $user) => $workflow->submit($record, $user), 'workflow.done.submitted'));
    }

    public static function startReview(): Action
    {
        return Action::make('startReview')
            ->label(__('workflow.actions.start_review'))
            ->icon(WorkflowStatus::InReview->getIcon())
            ->color('info')
            ->visible(fn (CourtCase|Hearing $record): bool => $record->status === WorkflowStatus::Submitted && Gate::allows('review', $record))
            ->action(self::run(fn (WorkflowService $workflow, CourtCase|Hearing $record, User $user) => $workflow->startReview($record, $user), 'workflow.done.in_review'));
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label(__('workflow.actions.approve'))
            ->icon(WorkflowStatus::Approved->getIcon())
            ->color('success')
            ->modalHeading(__('workflow.actions.approve'))
            ->schema([
                Textarea::make('comment')->label(__('workflow.comment_optional'))->rows(2),
            ])
            ->visible(fn (CourtCase|Hearing $record): bool => $record->status->isAwaitingReview() && Gate::allows('review', $record))
            ->action(function (CourtCase|Hearing $record, array $data, Action $action): void {
                self::run(fn (WorkflowService $workflow, CourtCase|Hearing $record, User $user) => $workflow->approve($record, $user, $data['comment'] ?? null), 'workflow.done.approved')($record, $action);
            });
    }

    public static function returnForCorrection(): Action
    {
        return Action::make('returnForCorrection')
            ->label(__('workflow.actions.return'))
            ->icon(WorkflowStatus::Returned->getIcon())
            ->color('danger')
            ->modalHeading(__('workflow.actions.return'))
            ->modalDescription(__('workflow.actions.return_help'))
            ->schema([
                Textarea::make('comment')->label(__('workflow.comment'))->required()->rows(4),
            ])
            ->visible(fn (CourtCase|Hearing $record): bool => in_array($record->status, [WorkflowStatus::Submitted, WorkflowStatus::InReview, WorkflowStatus::Approved], true) && Gate::allows('review', $record))
            ->action(function (CourtCase|Hearing $record, array $data, Action $action): void {
                self::run(fn (WorkflowService $workflow, CourtCase|Hearing $record, User $user) => $workflow->returnWithComment($record, $user, (string) $data['comment']), 'workflow.done.returned')($record, $action);
            });
    }

    public static function publish(): Action
    {
        return Action::make('publish')
            ->label(__('workflow.actions.publish'))
            ->icon(WorkflowStatus::Published->getIcon())
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('workflow.actions.publish_help'))
            ->visible(fn (CourtCase|Hearing $record): bool => Gate::allows('publish', $record))
            ->action(self::run(fn (WorkflowService $workflow, CourtCase|Hearing $record, User $user) => $workflow->publish($record, $user), 'workflow.done.published'));
    }

    public static function unpublish(): Action
    {
        return Action::make('unpublish')
            ->label(__('workflow.actions.unpublish'))
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (CourtCase|Hearing $record): bool => Gate::allows('unpublish', $record))
            ->action(self::run(fn (WorkflowService $workflow, CourtCase|Hearing $record, User $user) => $workflow->unpublish($record, $user), 'workflow.done.unpublished'));
    }

    /**
     * What changed since the last reviewed version.
     */
    public static function changes(): Action
    {
        return Action::make('viewChanges')
            ->label(__('workflow.actions.changes'))
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('gray')
            ->modalHeading(__('workflow.actions.changes'))
            ->modalWidth('4xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('workflow.actions.close'))
            ->modalContent(fn (CourtCase|Hearing $record) => view('filament.admin.workflow-diff', [
                'diff' => app(WorkflowService::class)->diff($record),
            ]))
            ->visible(fn (CourtCase|Hearing $record): bool => $record->status->isAwaitingReview() && Gate::allows('review', $record));
    }

    /**
     * @param  Closure(WorkflowService, CourtCase|Hearing, User): void  $operation
     * @return Closure(CourtCase|Hearing, Action): void
     */
    private static function run(Closure $operation, string $successKey): Closure
    {
        return function (CourtCase|Hearing $record, Action $action) use ($operation, $successKey): void {
            $user = auth()->user();

            if (! $user instanceof User) {
                abort(403);
            }

            try {
                $operation(app(WorkflowService::class), $record, $user);
            } catch (ValidationException $exception) {
                Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();
                $action->halt();
            }

            Notification::make()->title(__($successKey))->success()->send();
        };
    }
}
