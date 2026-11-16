{{-- Comment thread of a hearing report (App\Livewire\Admin\ReportComments). --}}
<div class="cw-comments">
    @if ($comments->isEmpty())
        <p class="cw-comments-empty">{{ __('reports.comments.empty') }}</p>
    @else
        <ol class="cw-comments-list">
            @foreach ($comments as $comment)
                <li @class(['cw-comment', 'cw-comment-author' => $comment->user_id !== null && $comment->user_id === $authorId]) wire:key="comment-{{ $comment->id }}">
                    <span class="cw-comment-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::of((string) ($comment->user?->name ?? '?'))->trim()->substr(0, 1)->upper() }}</span>
                    <div class="cw-comment-body">
                        <p class="cw-comment-meta">
                            <span class="cw-comment-name">{{ $comment->user?->name ?? __('reports.comments.deleted_user') }}</span>
                            <span class="cw-comment-role">{{ $comment->user_id === $authorId ? __('reports.comments.author') : __('reports.comments.editor') }}</span>
                            <time datetime="{{ $comment->created_at?->toIso8601String() }}">{{ $comment->created_at?->timezone(config('courtwatch.display_timezone'))->format(config('courtwatch.datetime_format')) }}</time>
                        </p>
                        <p class="cw-comment-text">{{ $comment->body }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    @if ($canComment)
        <form wire:submit="post" class="cw-comment-form">
            <label for="report-comment-{{ $reportId }}" class="sr-only">{{ __('reports.comments.field') }}</label>
            <textarea
                id="report-comment-{{ $reportId }}"
                wire:model="body"
                rows="3"
                maxlength="5000"
                class="cw-comment-input"
                placeholder="{{ __('reports.comments.placeholder') }}"
            ></textarea>
            @error('body')
                <p class="cw-comment-error">{{ $message }}</p>
            @enderror
            <div class="cw-comment-actions">
                <x-filament::button type="submit" color="info" icon="heroicon-o-chat-bubble-left-right" wire:loading.attr="disabled" wire:target="post">
                    {{ __('reports.comments.post') }}
                </x-filament::button>
            </div>
        </form>
    @endif
</div>
