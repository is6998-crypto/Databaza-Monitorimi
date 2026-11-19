<?php

/*
|--------------------------------------------------------------------------
| Livewire
|--------------------------------------------------------------------------
| Only the settings CourtWatch changes; everything else keeps Livewire's defaults
| (vendor/livewire/livewire/config/livewire.php). A top-level key given here replaces
| the package's whole entry, so each entry is complete.
*/

return [

    /*
    | Temporary file uploads (every Filament FileUpload: documents, publications, CSV imports).
    |
    | The browser sends the file to this application, which keeps it on the local disk until the
    | form is saved and then stores it where the field says (private S3 for documents).
    |
    | Livewire's default is the default filesystem disk. That is S3 here, and with S3 Livewire has
    | the BROWSER upload straight to the bucket through a pre-signed URL: in Docker that URL points
    | at http://s3:9000, which only exists inside Docker, and a real bucket would also need CORS.
    | Every upload failed ("… failed to upload"). Override with LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK.
    |
    | The size cap must cover the largest field: publications allow 50 MB (documents 20 MB, CSV 5 MB);
    | each field still applies its own limit. Livewire's default was 12 MB.
    */
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', 'local'),
        'rules' => ['required', 'file', 'max:51200'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

];
