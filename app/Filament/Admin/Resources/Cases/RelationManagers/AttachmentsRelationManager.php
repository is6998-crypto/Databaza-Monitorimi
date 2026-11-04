<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Models\Attachment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * Documents (PDFs, photos): private storage, size/type limits, signed expiring download links.
 * Used by both the case and the hearing workspaces.
 */
class AttachmentsRelationManager extends RelationManager
{
    use AuthorizesThroughCase;

    protected static string $relationship = 'attachments';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedPaperClip;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('attachments.title');
    }

    public function form(Schema $schema): Schema
    {
        $disk = (string) config('courtwatch.attachments.disk');

        return $schema->components([
            FileUpload::make('path')
                ->label(__('attachments.file'))
                ->disk($disk)
                ->directory(fn (): string => 'attachments/'.strtolower(class_basename($this->getOwnerRecord())).'/'.$this->getOwnerRecord()->getKey())
                ->visibility('private')
                ->acceptedFileTypes((array) config('courtwatch.attachments.mimes'))
                ->maxSize((int) config('courtwatch.attachments.max_kb'))
                ->storeFileNamesIn('original_name')
                ->required()
                ->helperText(__('attachments.help')),
            TextInput::make('description')->label(__('attachments.description'))->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('uploader')->latest())
            ->columns([
                TextColumn::make('original_name')->label(__('attachments.name'))->icon(Heroicon::OutlinedDocument)->wrap(),
                TextColumn::make('description')->label(__('attachments.description'))->placeholder('—')->toggleable(),
                TextColumn::make('size')->label(__('attachments.size'))->formatStateUsing(fn (int $state): string => Number::fileSize($state)),
                TextColumn::make('uploader.name')->label(__('attachments.uploaded_by'))->toggleable(),
                TextColumn::make('created_at')->label(__('attachments.uploaded_at'))->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('attachments.add'))
                    ->mutateDataUsing(function (array $data): array {
                        $disk = (string) config('courtwatch.attachments.disk');
                        $path = (string) $data['path'];

                        return [
                            ...$data,
                            'disk' => $disk,
                            'mime_type' => (string) (Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream'),
                            'size' => (int) Storage::disk($disk)->size($path),
                            'uploaded_by' => auth()->id(),
                            'original_name' => (string) ($data['original_name'] ?? basename($path)),
                        ];
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('attachments.download'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (Attachment $record): string => $record->signedUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Attachment $record): bool => Gate::allows('view', $record)),
                DeleteAction::make()
                    ->visible(fn (Attachment $record): bool => Gate::allows('delete', $record)),
            ])
            ->emptyStateHeading(__('attachments.empty'));
    }
}
