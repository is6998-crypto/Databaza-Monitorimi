<?php

namespace App\Filament\Admin\Resources\Researchers;

use App\Enums\NavigationGroup;
use App\Enums\ResearcherStatus;
use App\Filament\Admin\Resources\Researchers\Pages\ListResearcherProfiles;
use App\Filament\Admin\Resources\Researchers\Pages\ViewResearcherProfile;
use App\Models\ResearcherProfile;
use App\Models\User;
use App\Services\Research\ResearcherReview;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Researcher accounts: review registrations, approve, reject or suspend (proposal B6.8).
 */
class ResearcherProfileResource extends Resource
{
    protected static ?string $model = ResearcherProfile::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Administration;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'researchers';

    public static function getModelLabel(): string
    {
        return __('research.admin.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('research.admin.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = ResearcherProfile::query()->where('status', ResearcherStatus::Pending->value)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'reviewer']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label(__('research.admin.fields.name'))->searchable(),
                TextColumn::make('user.email')->label(__('research.admin.fields.email'))->searchable()->copyable(),
                TextColumn::make('institution')->label(__('research.register.institution'))->searchable()->wrap(),
                TextColumn::make('status')->label(__('research.admin.fields.status'))->badge()->color(fn (ResearcherStatus $state): string => self::statusColor($state)),
                IconColumn::make('agreement')
                    ->label(__('research.admin.fields.agreement'))
                    ->state(fn (ResearcherProfile $record): bool => $record->hasAcceptedCurrentAgreement())
                    ->boolean(),
                TextColumn::make('created_at')->label(__('research.admin.fields.registered_at'))->dateTime()->sortable(),
                TextColumn::make('reviewed_at')->label(__('research.admin.fields.reviewed_at'))->dateTime()->placeholder('—')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('research.admin.fields.status'))->options(ResearcherStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                ...self::reviewActions(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                Section::make(__('research.admin.sections.applicant'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('user.name')->label(__('research.admin.fields.name')),
                        TextEntry::make('user.email')->label(__('research.admin.fields.email'))->copyable(),
                        TextEntry::make('institution')->label(__('research.register.institution')),
                        TextEntry::make('position')->label(__('research.register.position'))->placeholder('—'),
                        TextEntry::make('research_purpose')->label(__('research.register.purpose'))->columnSpanFull()->prose(),
                        TextEntry::make('created_at')->label(__('research.admin.fields.registered_at'))->dateTime(),
                    ]),
                Section::make(__('research.admin.sections.review'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status')->label(__('research.admin.fields.status'))->badge()->color(fn (ResearcherStatus $state): string => self::statusColor($state)),
                        TextEntry::make('reviewer.name')->label(__('research.admin.fields.reviewer'))->placeholder('—'),
                        TextEntry::make('reviewed_at')->label(__('research.admin.fields.reviewed_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('review_note')->label(__('research.admin.fields.note'))->placeholder('—'),
                        IconEntry::make('agreement')
                            ->label(__('research.admin.fields.agreement'))
                            ->state(fn (ResearcherProfile $record): bool => $record->hasAcceptedCurrentAgreement())
                            ->boolean(),
                        TextEntry::make('agreement_accepted_at')->label(__('research.admin.fields.agreement_accepted_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('tokens')
                            ->label(__('research.admin.fields.tokens'))
                            ->state(fn (ResearcherProfile $record): int => $record->user->tokens()->count()),
                    ]),
            ]);
    }

    /**
     * @return list<Action>
     */
    public static function reviewActions(): array
    {
        $decision = fn (string $name, ResearcherStatus $status, string $color, Heroicon $icon, bool $noteRequired): Action => Action::make($name)
            ->label(__('research.admin.actions.'.$name))
            ->icon($icon)
            ->color($color)
            ->authorize(fn (ResearcherProfile $record): bool => auth()->user()?->can('approve', $record) ?? false)
            ->visible(fn (ResearcherProfile $record): bool => self::canMoveTo($record->status, $status))
            ->requiresConfirmation()
            ->modalDescription(__('research.admin.actions.'.$name.'_description'))
            ->schema([
                Textarea::make('note')
                    ->label(__('research.admin.fields.note'))
                    ->helperText(__('research.admin.note_help'))
                    ->required($noteRequired)
                    ->maxLength(2000)
                    ->rows(3),
            ])
            ->action(function (ResearcherProfile $record, array $data) use ($status, $name): void {
                $user = auth()->user();
                abort_unless($user instanceof User, 403);

                app(ResearcherReview::class)->decide($record, $status, $user, $data['note'] ?? null);

                Notification::make()->title(__('research.admin.actions.'.$name.'_done'))->success()->send();
            });

        return [
            $decision('approve', ResearcherStatus::Approved, 'success', Heroicon::OutlinedCheckBadge, false),
            $decision('reject', ResearcherStatus::Rejected, 'danger', Heroicon::OutlinedXCircle, true),
            $decision('suspend', ResearcherStatus::Suspended, 'warning', Heroicon::OutlinedNoSymbol, true),
        ];
    }

    public static function canMoveTo(ResearcherStatus $from, ResearcherStatus $to): bool
    {
        return match ($to) {
            ResearcherStatus::Approved => $from !== ResearcherStatus::Approved,
            ResearcherStatus::Rejected => $from === ResearcherStatus::Pending,
            ResearcherStatus::Suspended => $from === ResearcherStatus::Approved,
            ResearcherStatus::Pending => false,
        };
    }

    public static function statusColor(ResearcherStatus $status): string
    {
        return match ($status) {
            ResearcherStatus::Pending => 'warning',
            ResearcherStatus::Approved => 'success',
            ResearcherStatus::Rejected => 'danger',
            ResearcherStatus::Suspended => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResearcherProfiles::route('/'),
            'view' => ViewResearcherProfile::route('/{record}'),
        ];
    }
}
