<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\Role;
use App\Filament\Admin\Pages\ReviewQueue;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The band at the top of the staff dashboard: whose workspace this is (name, role, today's date) and the
 * next things this person can do. Every action is shown only when the panel's own access checks allow it
 * (the policies still decide on the target page).
 */
class WelcomeWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.welcome';

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $today = Carbon::now((string) config('courtwatch.display_timezone', 'Europe/Belgrade'))->locale(app()->getLocale());

        $actions = [];

        if (ReviewQueue::canAccess()) {
            $actions[] = ['label' => __('workflow.queue.title'), 'url' => ReviewQueue::getUrl(), 'count' => ReviewQueue::getNavigationBadge(), 'icon' => 'heroicon-o-inbox-stack'];
        }

        if (CourtCaseResource::canCreate()) {
            $actions[] = ['label' => __('dashboard.welcome.new_case'), 'url' => CourtCaseResource::getUrl('create'), 'count' => null, 'icon' => 'heroicon-o-folder-plus'];
        }

        if (HearingResource::canCreate()) {
            $actions[] = ['label' => __('dashboard.welcome.new_hearing'), 'url' => HearingResource::getUrl('create'), 'count' => null, 'icon' => 'heroicon-o-calendar-days'];
        }

        if ($user->hasRole(Role::Monitor->value)) {
            $actions[] = ['label' => __('dashboard.welcome.monitor_app'), 'url' => route('monitor.app'), 'count' => null, 'icon' => 'heroicon-o-device-phone-mobile'];
        }

        $role = collect(Role::staff())->first(fn (Role $role): bool => $user->hasRole($role->value));

        return [
            'firstName' => Str::of((string) $user->name)->trim()->before(' ')->title()->toString(),
            'role' => $role?->getLabel(),
            'today' => Str::ucfirst($today->translatedFormat('l')).', '.$today->format('d.m.Y'),
            'actions' => $actions,
        ];
    }
}
