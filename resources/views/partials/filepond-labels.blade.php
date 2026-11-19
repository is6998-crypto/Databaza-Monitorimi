{{--
    The upload widget (FilePond, bundled in Filament's file upload) ships no Albanian, so on Albanian pages its
    status messages ("Uploading", "Upload complete", "tap to undo"…) were English. Every widget announces itself with
    a "FilePond:init" DOM event carrying its instance; the labels from lang/{locale}/app.php are set on it then.
    The drop-zone text itself is the field's placeholder (AppServiceProvider), so it is not overridden here.
--}}
<script>
    document.addEventListener('FilePond:init', (event) => {
        event.detail?.pond?.setOptions(@js(trans('app.file_upload.labels')));
    });
</script>
