{{--
    The page's dealt moment, pinned-chapters (the skill's references/moments.md): the methodology steps, each a
    title and its first sentence from the methodology page, beside one frame that shows the matching part of the
    real platform (the hearing form, the review workflow, the indicators, the anonymized record).
    The kit's moment.js holds the section on screen while the steps open; at rest, on a phone and under reduced
    motion every step stands with its picture.
--}}
@php
    use App\Enums\WorkflowStatus;
    use App\Services\Anonymizer;
    $method = trans('portal.pages.methodology');
    $sentence = fn (string $text): string => \Illuminate\Support\Str::of($text)->before('. ')->finish('.')->toString();
    $steps = collect($method['sections'])->take(4)->values();
    $sample = $recentCases->first();
@endphp
<section class="portal-moment bg-white" id="how-it-works" data-moment="pinned-chapters" aria-labelledby="how-title">
    <div class="wrap portal-wrap mo-pc__grid py-20 sm:py-24">
        <div class="mo-pc__text">
            <div>
                <h2 id="how-title" class="portal-section-title">{{ $method['title'] }}</h2>
                <p class="mt-4 max-w-xl text-lg leading-relaxed text-slate-700">{{ $method['intro'] }}</p>
            </div>
            <ol class="mo-pc__steps">
                @foreach ($steps as $index => $step)
                    <li class="mo-pc__step">
                        <h3>{{ $step['heading'] }}</h3>
                        <p>{{ $sentence($step['paragraphs'][0]) }}</p>
                        <figure class="mo-pc__visual mo-pc__visual--lit" role="img" aria-label="{{ $step['heading'] }}">
                            <div class="w-full max-w-md rounded-xl bg-white p-5 text-left text-navy-900 shadow-[0_24px_48px_-24px_rgb(0_0_0_/_0.5)]">
                                @switch($index)
                                    @case(0)
                                        {{-- The hearing form a monitor fills in. --}}
                                        @foreach ([['is_public', 'public', 'closed', 0], ['started_late', 'late', 'on_time', 1]] as [$field, $a, $b, $chosen])
                                            <p class="text-[15px] font-semibold @if (! $loop->first) mt-5 @endif">{{ __('hearings.fields.'.$field) }}</p>
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                @foreach ([$a, $b] as $i => $answer)
                                                    <span @class(['flex min-h-11 items-center justify-center rounded-xl border text-[15px] font-bold', 'border-gold-400 bg-gold-400' => $i === $chosen, 'border-slate-300' => $i !== $chosen])>{{ __('hearings.answers.'.$answer) }}</span>
                                                @endforeach
                                            </div>
                                        @endforeach
                                        @break
                                    @case(1)
                                        {{-- The review: every record moves through these states. --}}
                                        <ol class="space-y-2.5">
                                            @foreach ([WorkflowStatus::Draft, WorkflowStatus::Submitted, WorkflowStatus::InReview, WorkflowStatus::Approved, WorkflowStatus::Published] as $status)
                                                <li class="flex items-center gap-3 text-[15px] font-semibold">
                                                    <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full', 'bg-navy-900 text-white' => $loop->index < 3, 'border-[3px] border-gold-400' => $loop->index === 3, 'border-2 border-slate-300' => $loop->index > 3])>
                                                        @if ($loop->index < 3)<svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>@endif
                                                    </span>
                                                    <span @class(['text-slate-500' => $loop->index > 3])>{{ $status->getLabel() }}</span>
                                                </li>
                                            @endforeach
                                        </ol>
                                        @break
                                    @case(2)
                                        {{-- The indicators: rates over monitored hearings. --}}
                                        @foreach (['postponement_rate', 'late_rate', 'public_rate'] as $key)
                                            <div @class(['mt-4' => ! $loop->first])>
                                                <p class="flex items-baseline justify-between gap-3 text-[15px] font-semibold"><span>{{ __('portal.stats.'.$key) }}</span><span class="tabular-nums">{{ Anonymizer::display($headline[$key], ' %') }}</span></p>
                                                <span class="mt-2 block h-2.5 overflow-hidden rounded-full bg-navy-50"><span class="block h-full rounded-full {{ $loop->first ? 'bg-gold-400' : 'bg-navy-900' }}" style="width: {{ min(100, max(0, (float) ($headline[$key] ?? 0))) }}%"></span></span>
                                            </div>
                                        @endforeach
                                        @break
                                    @default
                                        {{-- The public record: an anonymous reference and suppressed small numbers. --}}
                                        @if ($sample)
                                            <span class="portal-chip bg-gold-100 text-navy-900">{{ $sample['type'] }}</span>
                                            <p class="mt-3 text-[13px] font-semibold tracking-wide text-slate-600 tabular-nums">{{ $sample['ref'] }}</p>
                                            <p class="text-lg font-bold">{{ $sample['court'] }}</p>
                                        @endif
                                        <dl class="mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-navy-900/10 ring-1 ring-navy-900/10">
                                            <div class="bg-white p-3"><dt class="portal-meta">{{ __('portal.cases.hearings') }}</dt><dd class="mt-1 text-2xl font-extrabold tabular-nums">{{ Anonymizer::display(null) }}</dd></div>
                                            <div class="bg-white p-3"><dt class="portal-meta">{{ __('portal.cases.postponed') }}</dt><dd class="mt-1 text-2xl font-extrabold tabular-nums">{{ Anonymizer::display(null) }}</dd></div>
                                        </dl>
                                @endswitch
                            </div>
                        </figure>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('portal.methodology') }}" class="group inline-flex min-h-11 items-center gap-2 justify-self-start text-[15px] font-bold text-navy-900 underline decoration-gold-400 decoration-2 underline-offset-4 hover:decoration-navy-900">
                {{ __('portal.home.read_methodology') }}
                <svg class="portal-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </div>
        <div class="mo-pc__visuals frame"></div>
    </div>
</section>
