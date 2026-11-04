<?php

namespace App\Http\Controllers\Monitor;

use App\Enums\DelayCause;
use App\Enums\HearingType;
use App\Enums\Participant;
use App\Enums\PhoneUser;
use App\Enums\PostponementReason;
use App\Enums\Recording;
use App\Enums\Venue;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Http\Controllers\Controller;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\User;
use App\Services\WorkflowService;
use App\Support\HearingRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Installable monitor app (PWA) for recording hearings on a phone, with offline drafts kept in
 * IndexedDB and synced here when the device is back online.
 */
class MonitorAppController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('create', Hearing::class);

        return view('monitor.app', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Everything the app needs to work offline: the monitor's cases (with party initials only),
     * answer options and recent submissions.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        Gate::authorize('create', Hearing::class);

        /** @var User $user */
        $user = $request->user();
        $monitor = $user->monitor;

        $cases = CourtCase::query()
            ->visibleTo($user)
            ->when($user->isMonitorOnly() && $monitor !== null, fn ($query) => $query->orderByRaw('CASE WHEN assigned_monitor_id = ? THEN 0 ELSE 1 END', [$monitor->id]))
            ->whereNotIn('status', [WorkflowStatus::Published->value])
            ->with(['court', 'judge', 'prosecutor', 'parties'])
            ->latest('updated_at')
            ->limit(200)
            ->get()
            ->map(fn (CourtCase $case): array => [
                'id' => $case->id,
                'label' => $case->display_label,
                'type' => $case->type->getLabel(),
                'court' => $case->court?->label(),
                'judge' => $case->judge?->name,
                'prosecutor' => $case->prosecutor?->name,
                'parties' => $case->parties->map(fn (CaseParty $party): array => [
                    'id' => $party->id,
                    // Only role + initials are stored on the device.
                    'label' => $party->role->getLabel().': '.$this->initials((string) $party->name),
                    'present_by_default' => in_array($party->role->value, ['accused', 'legal_entity'], true),
                ])->values()->all(),
            ])
            ->values();

        $recent = Hearing::query()
            ->where('created_by', $user->id)
            ->with('case.court')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Hearing $hearing): array => [
                'id' => $hearing->id,
                'client_uuid' => $hearing->client_uuid,
                'date' => $hearing->hearing_date->format('d.m.Y'),
                'case' => $hearing->case->display_label,
                'status' => $hearing->status->value,
                'status_label' => $hearing->status->getLabel(),
                'url' => HearingResource::getUrl('view', ['record' => $hearing], panel: 'admin'),
            ]);

        return response()->json([
            'user' => ['name' => $user->name, 'monitor_id' => $monitor?->id],
            'today' => Carbon::now((string) config('courtwatch.display_timezone'))->toDateString(),
            'csrf' => csrf_token(),
            'cases' => $cases,
            'recent' => $recent,
            'options' => [
                'hearing_types' => HearingType::options(),
                'venues' => Venue::options(),
                'delay_causes' => DelayCause::options(),
                'postponement_reasons' => PostponementReason::options(),
                'phone_users' => PhoneUser::options(),
                'participants' => Participant::options(),
                'recordings' => Recording::options(),
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Creates (or returns, if already synced) a hearing from an offline draft.
     */
    public function storeHearing(Request $request, WorkflowService $workflow): JsonResponse
    {
        Gate::authorize('create', Hearing::class);

        /** @var User $user */
        $user = $request->user();
        $data = $request->validate(HearingRules::rules());

        $existing = Hearing::query()->where('client_uuid', $data['client_uuid'])->first();

        if ($existing !== null) {
            abort_unless($existing->created_by === $user->id, 409);

            return $this->hearingResponse($existing, 200);
        }

        $case = CourtCase::query()->findOrFail($data['case_id']);
        Gate::authorize('view', $case);

        $hearing = DB::transaction(function () use ($data, $case, $user): Hearing {
            $hearing = new Hearing;
            $hearing->fill([
                ...HearingRules::attributes($data),
                'court_id' => $case->court_id,
                'judge_id' => $case->judge_id,
                'prosecutor_id' => $case->prosecutor_id,
                'monitor_id' => $user->monitor?->id,
            ]);
            $hearing->save();

            $partyIds = $case->parties()->whereIn('id', (array) ($data['present_party_ids'] ?? []))->pluck('id');
            $hearing->presentParties()->sync($partyIds);

            return $hearing;
        });

        if ((bool) ($data['submit'] ?? false)) {
            $workflow->submit($hearing, $user);
        }

        return $this->hearingResponse($hearing->fresh() ?? $hearing, 201);
    }

    private function hearingResponse(Hearing $hearing, int $status): JsonResponse
    {
        return response()->json([
            'id' => $hearing->id,
            'client_uuid' => $hearing->client_uuid,
            'status' => $hearing->status->value,
            'status_label' => $hearing->status->getLabel(),
            'url' => HearingResource::getUrl('view', ['record' => $hearing], panel: 'admin'),
        ], $status);
    }

    private function initials(string $name): string
    {
        $letters = (string) preg_replace('/[^\p{L}\s]/u', '', $name);

        return collect(preg_split('/\s+/', trim($letters)) ?: [])
            ->filter()
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)).'.')
            ->implode(' ');
    }
}
