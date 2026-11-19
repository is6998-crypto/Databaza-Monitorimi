<?php

use App\Enums\Role;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Cases\Pages\ViewCourtCase;
use App\Filament\Admin\Resources\Cases\RelationManagers\AttachmentsRelationManager;
use App\Models\Attachment;
use App\Models\CourtCase;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('keeps temporary uploads on the application even when files are stored on S3', function () {
    // As in Docker and production: the default disk is S3 at an address the browser cannot reach.
    config(['filesystems.default' => 's3', 'filesystems.disks.s3.endpoint' => 'http://s3:9000']);

    // The browser therefore uploads to this application, never straight to the bucket. (Under tests Livewire
    // swaps the disk itself, so the configured value is checked.)
    $disk = (string) config('livewire.temporary_file_upload.disk');

    expect($disk)->toBe('local')
        ->and(config("filesystems.disks.{$disk}.driver"))->toBe('local')
        // Large enough for the largest field (publications, 50 MB); each field applies its own limit.
        ->and(FileUploadConfiguration::rules())->toContain('max:51200');
});

it('uploads a PDF and an image to a case and stores them privately', function (string $name, string $mime, int $kilobytes) {
    Storage::fake('local');
    config(['courtwatch.attachments.disk' => 'local']);

    $case = CourtCase::factory()->create();
    $editor = userWithRole(Role::Editor);
    $this->actingAs($editor);

    Livewire::test(AttachmentsRelationManager::class, ['ownerRecord' => $case, 'pageClass' => ViewCourtCase::class])
        ->callTableAction(CreateAction::class, data: [
            'path' => UploadedFile::fake()->create($name, $kilobytes, $mime),
            'description' => 'Procesverbali',
        ])
        ->assertHasNoTableActionErrors();

    $attachment = Attachment::query()->where('attachable_id', $case->id)->sole();

    expect($attachment->original_name)->toBe($name)
        ->and($attachment->mime_type)->toBe($mime)
        ->and($attachment->uploaded_by)->toBe($editor->id)
        ->and($attachment->path)->toStartWith('attachments/courtcase/'.$case->id.'/');

    Storage::disk('local')->assertExists($attachment->path);
})->with([
    // 15 MB: over Livewire's old 12 MB cap, under the 20 MB the form promises.
    'a 15 MB PDF' => ['aktakuza.pdf', 'application/pdf', 15 * 1024],
    'a JPG photo' => ['foto.jpg', 'image/jpeg', 800],
    'a PNG image' => ['skema.png', 'image/png', 300],
]);

it('refuses other file types and files over 20 MB', function (string $name, string $mime, int $kilobytes) {
    Storage::fake('local');
    config(['courtwatch.attachments.disk' => 'local']);

    $case = CourtCase::factory()->create();
    $this->actingAs(userWithRole(Role::Editor));

    Livewire::test(AttachmentsRelationManager::class, ['ownerRecord' => $case, 'pageClass' => ViewCourtCase::class])
        ->callTableAction(CreateAction::class, data: ['path' => UploadedFile::fake()->create($name, $kilobytes, $mime)])
        ->assertHasTableActionErrors(['path']);

    expect(Attachment::query()->where('attachable_id', $case->id)->exists())->toBeFalse();
})->with([
    'a Word document' => ['raporti.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 100],
    'a 25 MB PDF' => ['i-madh.pdf', 'application/pdf', 25 * 1024],
]);

it('shows the drop zone in the page language', function (string $locale, string $text) {
    app()->setLocale($locale);
    $this->actingAs(userWithRole(Role::Editor));

    Livewire::test(AttachmentsRelationManager::class, ['ownerRecord' => CourtCase::factory()->create(), 'pageClass' => ViewCourtCase::class])
        ->mountTableAction(CreateAction::class)
        ->assertFormFieldExists('path', 'mountedActionSchema0', fn (FileUpload $field): bool => str_contains((string) $field->getPlaceholder(), $text));
})->with([
    'Albanian' => ['sq', 'Tërhiqni skedarin këtu'],
    'English' => ['en', 'Drag a file here'],
]);

it('gives the upload widget its status messages in the page language', function () {
    $this->actingAs(userWithRole(Role::Editor));

    $this->get('/locale/sq');
    $html = (string) $this->get(CourtCaseResource::getUrl('view', ['record' => CourtCase::factory()->create()]))->assertOk()->getContent();

    expect($html)->toContain("addEventListener('FilePond:init'")
        // Sent as JSON, so letters such as ë are escaped: an ASCII message is checked.
        ->and($html)->toContain('Duke u ngarkuar')
        ->and($html)->toContain('labelFileProcessingComplete');
});
