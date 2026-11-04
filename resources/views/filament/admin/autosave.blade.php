<div
    wire:poll.20s="autosaveDraft"
    x-data
    x-on:courtwatch:before-idle-logout.window="$wire.autosaveDraft()"
    x-on:courtwatch:idle-warning.window="$wire.autosaveDraft()"
    class="text-xs text-gray-500 dark:text-gray-400"
    data-autosave
>
    @if ($savedAt)
        {{ __('cases.drafts.saved_at', ['time' => $savedAt]) }}
    @else
        {{ __('cases.drafts.autosave_on') }}
    @endif
</div>
