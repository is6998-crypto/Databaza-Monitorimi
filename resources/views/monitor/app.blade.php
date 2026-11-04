<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="robots" content="noindex, nofollow">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="CourtWatch">
    <title>{{ __('monitor.title') }} · CourtWatch</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    @vite(['resources/css/monitor.css', 'resources/js/monitor.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
@php
    $config = [
        't' => trans('monitor'),
        'bootstrapUrl' => route('monitor.bootstrap'),
        'storeUrl' => route('monitor.hearings.store'),
        'serviceWorkerUrl' => asset('sw.js'),
    ];
@endphp
<div x-data="monitorApp(@js($config))" x-cloak class="mx-auto flex min-h-screen max-w-xl flex-col">

    {{-- Header --}}
    <header class="sticky top-0 z-20 bg-indigo-700 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))] text-white shadow">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wide text-indigo-200">CourtWatch</p>
                <h1 class="truncate text-lg font-semibold">{{ __('monitor.title') }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="online ? 'bg-emerald-500/90' : 'bg-amber-500'" x-text="online ? t.online : t.offline" data-network-status></span>
                <a href="{{ url('/admin') }}" class="rounded-lg bg-white/10 px-2.5 py-1.5 text-xs font-medium hover:bg-white/20">{{ __('monitor.panel') }}</a>
            </div>
        </div>
        <p class="mt-1 text-xs text-indigo-100" x-show="syncing">{{ __('monitor.syncing') }}</p>
        <p class="mt-1 text-xs text-indigo-100" x-show="!syncing && pendingCount() > 0" x-text="t.pending_count.replace(':count', pendingCount())"></p>
    </header>

    <div x-show="authLost" class="m-4 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
        {{ __('monitor.auth_lost') }}
        <a href="{{ url('/admin/login') }}" class="font-semibold underline">{{ __('monitor.sign_in') }}</a>
    </div>
    <div x-show="notice" class="m-4 rounded-xl border border-red-300 bg-red-50 p-3 text-sm text-red-900" x-text="notice"></div>

    {{-- Home --}}
    <main x-show="screen === 'home'" class="flex-1 space-y-5 p-4">
        <button type="button" class="cw-primary w-full py-4 text-base" x-on:click="newDraft()" :disabled="!data" data-new-hearing>
            + {{ __('monitor.new_hearing') }}
        </button>
        <p x-show="!data" class="rounded-xl bg-white p-4 text-sm text-slate-600 shadow-sm">{{ __('monitor.first_time_online') }}</p>

        <section>
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('monitor.on_this_device') }}</h2>
                <button type="button" class="text-xs font-medium text-indigo-700" x-show="drafts.some(d => d._status === 'synced')" x-on:click="clearSynced()">{{ __('monitor.clear_synced') }}</button>
            </div>
            <p x-show="drafts.length === 0" class="rounded-xl bg-white p-4 text-sm text-slate-500 shadow-sm">{{ __('monitor.no_drafts') }}</p>
            <ul class="space-y-2" data-drafts>
                <template x-for="item in drafts" :key="item.client_uuid">
                    <li class="rounded-xl bg-white p-3 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium" x-text="caseLabel(item.case_id)"></p>
                                <p class="text-sm text-slate-500"><span x-text="item.hearing_date ? item.hearing_date.split('-').reverse().join('.') : ''"></span> · <span x-text="formatTime(item._updated_at)"></span></p>
                            </div>
                            <span class="cw-badge shrink-0" :class="{
                                'bg-slate-100 text-slate-700': item._status === 'draft',
                                'bg-amber-100 text-amber-800': item._status === 'pending',
                                'bg-emerald-100 text-emerald-800': item._status === 'synced',
                                'bg-red-100 text-red-800': item._status === 'error',
                            }" x-text="statusLabel(item)" data-draft-status></span>
                        </div>
                        <p x-show="item._status === 'error'" class="mt-2 text-sm text-red-700">{{ __('monitor.fix_errors') }}</p>
                        <div class="mt-3 flex gap-2">
                            <button type="button" class="cw-secondary" x-show="item._status !== 'synced'" x-on:click="editDraft(item)">{{ __('monitor.continue') }}</button>
                            <a class="cw-secondary" x-show="item._status === 'synced' && item._server" :href="item._server ? item._server.url : '#'">{{ __('monitor.open_in_panel') }}</a>
                            <button type="button" class="cw-ghost" x-show="item._status !== 'pending'" x-on:click="removeDraft(item)">{{ __('monitor.delete') }}</button>
                        </div>
                    </li>
                </template>
            </ul>
        </section>

        <section x-show="data && data.recent.length">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('monitor.recently_sent') }}</h2>
            <ul class="divide-y divide-slate-100 overflow-hidden rounded-xl bg-white shadow-sm">
                <template x-for="item in (data ? data.recent : [])" :key="item.id">
                    <li><a :href="item.url" class="flex items-center justify-between gap-3 p-3">
                        <span class="min-w-0"><span class="block truncate font-medium" x-text="item.case"></span><span class="text-sm text-slate-500" x-text="item.date"></span></span>
                        <span class="cw-badge bg-slate-100 text-slate-700" x-text="item.status_label"></span>
                    </a></li>
                </template>
            </ul>
        </section>

        <div class="pt-4 text-center">
            <button type="button" class="text-sm text-slate-500 underline" x-on:click="signOut()">{{ __('monitor.sign_out') }}</button>
        </div>
    </main>

    {{-- Hearing form --}}
    <main x-show="screen === 'form'" class="flex-1 space-y-4 p-4 pb-32" data-hearing-form>
        <template x-if="draft">
            <div class="space-y-4">
                <div x-show="Object.keys(draft._errors || {}).length" class="rounded-xl border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                    <p class="font-semibold">{{ __('monitor.fix_errors') }}</p>
                    <ul class="mt-1 list-disc pl-5"><template x-for="(messages, field) in draft._errors" :key="field"><li x-text="messages[0]"></li></template></ul>
                </div>

                <section class="cw-card">
                    <h2 class="cw-card-title">{{ __('hearings.fields.case') }}</h2>
                    <template x-if="!draft.case_id">
                        <div>
                            <input type="search" x-model="caseQuery" class="cw-input" placeholder="{{ __('monitor.search_case') }}" data-case-search>
                            <ul class="mt-2 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                                <template x-for="item in cases" :key="item.id">
                                    <li><button type="button" class="w-full p-3 text-left" x-on:click="selectCase(item.id)">
                                        <span class="block font-medium" x-text="item.label"></span>
                                        <span class="text-xs text-slate-500" x-text="item.type"></span>
                                    </button></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                    <template x-if="draft.case_id">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 text-sm">
                                <p class="font-semibold" x-text="caseLabel(draft.case_id)"></p>
                                <p class="text-slate-500" x-show="currentCase" x-text="currentCase ? [currentCase.judge, currentCase.prosecutor].filter(Boolean).join(' · ') : ''"></p>
                            </div>
                            <button type="button" class="cw-ghost shrink-0" x-on:click="draft.case_id = ''; draft.present_party_ids = []">{{ __('monitor.change') }}</button>
                        </div>
                    </template>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="block"><span class="cw-label">{{ __('hearings.fields.hearing_date') }}</span>
                            <input type="date" x-model="draft.hearing_date" :max="data ? data.today : ''" class="cw-input" data-hearing-date></label>
                        <label class="block"><span class="cw-label">{{ __('hearings.fields.hearing_type') }}</span>
                            <select x-model="draft.hearing_type" class="cw-input">
                                <option value="">—</option>
                                <template x-for="(label, value) in data.options.hearing_types" :key="value"><option :value="value" x-text="label"></option></template>
                            </select></label>
                    </div>
                </section>

                <section class="cw-card" x-show="currentCase && currentCase.parties.length">
                    <h2 class="cw-card-title">{{ __('hearings.fields.present_parties') }}</h2>
                    <div class="space-y-2">
                        <template x-for="party in (currentCase ? currentCase.parties : [])" :key="party.id">
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                                <input type="checkbox" class="size-5 accent-indigo-600" :checked="draft.present_party_ids.includes(party.id)" x-on:change="toggle('present_party_ids', party.id)">
                                <span x-text="party.label"></span>
                            </label>
                        </template>
                    </div>
                </section>

                <section class="cw-card">
                    <h2 class="cw-card-title">{{ __('hearings.fields.participants') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(label, value) in data.options.participants" :key="value">
                            <button type="button" class="cw-chip" :class="has('participants', value) && 'cw-chip-on'" x-on:click="toggle('participants', value)" x-text="label"></button>
                        </template>
                    </div>
                </section>

                <section class="cw-card space-y-5">
                    <h2 class="cw-card-title">{{ __('hearings.sections.session') }}</h2>

                    <div><p class="cw-label">{{ __('hearings.fields.venue') }}</p>
                        <div class="cw-segment">
                            <template x-for="(label, value) in data.options.venues" :key="value">
                                <button type="button" :class="draft.venue === value && 'cw-on'" x-on:click="draft.venue = value" x-text="label"></button>
                            </template>
                        </div></div>

                    <div><p class="cw-label">{{ __('hearings.fields.is_public') }}</p>
                        <div class="cw-segment">
                            <button type="button" :class="draft.is_public === true && 'cw-on'" x-on:click="draft.is_public = true">{{ __('hearings.answers.public') }}</button>
                            <button type="button" :class="draft.is_public === false && 'cw-on'" x-on:click="draft.is_public = false">{{ __('hearings.answers.closed') }}</button>
                        </div></div>

                    <div><p class="cw-label">{{ __('hearings.fields.started_late') }}</p>
                        <div class="cw-segment" data-question="started_late">
                            <button type="button" :class="draft.started_late === true && 'cw-on'" x-on:click="draft.started_late = true">{{ __('hearings.answers.late') }}</button>
                            <button type="button" :class="draft.started_late === false && 'cw-on'" x-on:click="draft.started_late = false">{{ __('hearings.answers.on_time') }}</button>
                        </div>
                        <div x-show="draft.started_late" class="mt-3 space-y-3 rounded-lg bg-slate-50 p-3">
                            <p class="cw-label">{{ __('hearings.fields.delay_cause') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(label, value) in data.options.delay_causes" :key="value">
                                    <button type="button" class="cw-chip" :class="draft.delay_cause === value && 'cw-chip-on'" x-on:click="draft.delay_cause = value" x-text="label"></button>
                                </template>
                            </div>
                            <label class="block"><span class="cw-label">{{ __('hearings.fields.delay_minutes') }}</span>
                                <input type="number" inputmode="numeric" min="16" max="600" x-model.number="draft.delay_minutes" class="cw-input"></label>
                        </div></div>

                    <div><p class="cw-label">{{ __('hearings.fields.was_postponed') }}</p>
                        <div class="cw-segment" data-question="was_postponed">
                            <button type="button" :class="draft.was_postponed === true && 'cw-on'" x-on:click="draft.was_postponed = true">{{ __('app.yes') }}</button>
                            <button type="button" :class="draft.was_postponed === false && 'cw-on'" x-on:click="draft.was_postponed = false">{{ __('app.no') }}</button>
                        </div>
                        <div x-show="draft.was_postponed" class="mt-3 rounded-lg bg-slate-50 p-3">
                            <p class="cw-label">{{ __('hearings.fields.postponement_reasons') }}</p>
                            <div class="flex flex-wrap gap-2" data-postponement-reasons>
                                <template x-for="(label, value) in data.options.postponement_reasons" :key="value">
                                    <button type="button" class="cw-chip" :class="has('postponement_reasons', value) && 'cw-chip-on'" x-on:click="toggle('postponement_reasons', value)" x-text="label"></button>
                                </template>
                            </div>
                        </div></div>

                    <div><p class="cw-label">{{ __('hearings.fields.recording') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(label, value) in data.options.recordings" :key="value">
                                <button type="button" class="cw-chip" :class="draft.recording === value && 'cw-chip-on'" x-on:click="draft.recording = value" x-text="label"></button>
                            </template>
                        </div></div>

                    <div><p class="cw-label">{{ __('hearings.fields.interpretation_needed') }}</p>
                        <div class="cw-segment">
                            <button type="button" :class="draft.interpretation_needed === true && 'cw-on'" x-on:click="draft.interpretation_needed = true">{{ __('app.yes') }}</button>
                            <button type="button" :class="draft.interpretation_needed === false && 'cw-on'" x-on:click="draft.interpretation_needed = false">{{ __('app.no') }}</button>
                        </div>
                        <div x-show="draft.interpretation_needed" class="mt-3 rounded-lg bg-slate-50 p-3">
                            <p class="cw-label">{{ __('hearings.fields.interpretation_provided') }}</p>
                            <div class="cw-segment">
                                <button type="button" :class="draft.interpretation_provided === true && 'cw-on'" x-on:click="draft.interpretation_provided = true">{{ __('app.yes') }}</button>
                                <button type="button" :class="draft.interpretation_provided === false && 'cw-on'" x-on:click="draft.interpretation_provided = false">{{ __('app.no') }}</button>
                            </div>
                        </div></div>

                    <div><p class="cw-label">{{ __('hearings.fields.phone_used') }}</p>
                        <div class="cw-segment">
                            <button type="button" :class="draft.phone_used === true && 'cw-on'" x-on:click="draft.phone_used = true">{{ __('app.yes') }}</button>
                            <button type="button" :class="draft.phone_used === false && 'cw-on'" x-on:click="draft.phone_used = false">{{ __('app.no') }}</button>
                        </div>
                        <div x-show="draft.phone_used" class="mt-3 rounded-lg bg-slate-50 p-3">
                            <p class="cw-label">{{ __('hearings.fields.phone_users') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(label, value) in data.options.phone_users" :key="value">
                                    <button type="button" class="cw-chip" :class="has('phone_users', value) && 'cw-chip-on'" x-on:click="toggle('phone_users', value)" x-text="label"></button>
                                </template>
                            </div>
                        </div></div>

                    <div><p class="cw-label">{{ __('hearings.fields.security_adequate') }}</p>
                        <div class="cw-segment">
                            <button type="button" :class="draft.security_adequate === true && 'cw-on'" x-on:click="draft.security_adequate = true">{{ __('app.yes') }}</button>
                            <button type="button" :class="draft.security_adequate === false && 'cw-on'" x-on:click="draft.security_adequate = false">{{ __('app.no') }}</button>
                        </div>
                        <label class="mt-3 block"><span class="cw-label">{{ __('hearings.fields.security_comment') }}</span>
                            <textarea x-model="draft.security_comment" rows="2" class="cw-input"></textarea></label></div>
                </section>

                <section class="cw-card">
                    <label class="block"><span class="cw-card-title">{{ __('hearings.fields.notes') }}</span>
                        <textarea x-model="draft.notes" rows="4" class="cw-input" placeholder="{{ __('hearings.help.notes') }}"></textarea></label>
                </section>

                <p class="text-center text-xs text-slate-500">{{ __('monitor.autosaved') }}</p>
            </div>
        </template>

        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur">
            <div class="mx-auto flex max-w-xl gap-2">
                <button type="button" class="cw-secondary flex-1 py-3" x-on:click="keepDraft()" data-keep-draft>{{ __('monitor.save_draft') }}</button>
                <button type="button" class="cw-primary flex-1 py-3" x-on:click="sendForReview()" data-send-review>{{ __('monitor.send_for_review') }}</button>
            </div>
        </div>
    </main>

    <form id="monitor-logout" method="POST" action="{{ route('filament.admin.auth.logout') }}" class="hidden">@csrf</form>
</div>

@include('partials.idle-timeout', ['logoutUrl' => route('filament.admin.auth.logout'), 'loginUrl' => url('/admin/login')])
</body>
</html>
