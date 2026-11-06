<?php

use App\Enums\Role;
use App\Models\Attachment;
use App\Models\CourtCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

function attachmentFor(CourtCase $case): Attachment
{
    Storage::fake('local');
    Storage::disk('local')->put('attachments/case/'.$case->id.'/doc.pdf', '%PDF-1.4 fake');

    return Attachment::query()->create([
        'attachable_type' => $case->getMorphClass(),
        'attachable_id' => $case->id,
        'disk' => 'local',
        'path' => 'attachments/case/'.$case->id.'/doc.pdf',
        'original_name' => 'aktakuza.pdf',
        'mime_type' => 'application/pdf',
        'size' => 13,
    ]);
}

it('serves private files only through a valid signed link', function () {
    $case = CourtCase::factory()->create();
    $attachment = attachmentFor($case);
    $this->actingAs(userWithRole(Role::Editor));

    $this->get($attachment->signedUrl())->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('attachments.download', $attachment))->assertForbidden();
});

it('refuses expired links', function () {
    $case = CourtCase::factory()->create();
    $attachment = attachmentFor($case);
    $this->actingAs(userWithRole(Role::Editor));

    $expired = URL::temporarySignedRoute('attachments.download', now()->subMinute(), ['attachment' => $attachment->id]);

    $this->get($expired)->assertForbidden();
});

it('checks that the user may see the case, even with a valid link', function () {
    $case = CourtCase::factory()->create(['court_id' => basicCourt(1)->id]);
    $attachment = attachmentFor($case);
    $this->actingAs(monitorUser([basicCourt(0)->id]));

    $this->get($attachment->signedUrl())->assertForbidden();
});

it('requires a signed-in user', function () {
    $case = CourtCase::factory()->create();
    $attachment = attachmentFor($case);

    $this->get($attachment->signedUrl())->assertRedirect();
});
