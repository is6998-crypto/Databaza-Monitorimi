<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Enums\Role;
use App\Models\Reference\Monitor;
use App\Models\User;

/**
 * Keeps the monitors row (and its court scope) in sync with the user form.
 */
trait SyncsMonitorProfile
{
    /** @var array{code: ?string, phone: ?string, courts: list<int>} */
    protected array $monitorData = ['code' => null, 'phone' => null, 'courts' => []];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function pullMonitorData(array $data): array
    {
        $this->monitorData = [
            'code' => $data['monitor_code'] ?? null,
            'phone' => $data['monitor_phone'] ?? null,
            'courts' => array_map('intval', (array) ($data['monitor_court_ids'] ?? [])),
        ];

        unset($data['monitor_code'], $data['monitor_phone'], $data['monitor_court_ids']);

        return $data;
    }

    protected function syncMonitorProfile(User $user): void
    {
        $user->refresh();

        if (! $user->hasRole(Role::Monitor->value)) {
            $user->monitor?->update(['is_active' => false]);

            return;
        }

        /** @var Monitor $monitor */
        $monitor = Monitor::withTrashed()->firstOrNew(['user_id' => $user->id]);
        $monitor->fill([
            'code' => $this->monitorData['code'],
            'phone' => $this->monitorData['phone'],
            'is_active' => true,
        ]);
        $monitor->save();

        if ($monitor->trashed()) {
            $monitor->restore();
        }
        $monitor->courts()->sync($this->monitorData['courts']);
    }
}
