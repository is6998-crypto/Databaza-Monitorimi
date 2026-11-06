<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use SyncsMonitorProfile;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [UserResource::resetMfaAction()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record;
        $monitor = $user->monitor;

        $data['monitor_code'] = $monitor?->code;
        $data['monitor_phone'] = $monitor?->phone;
        $data['monitor_court_ids'] = $monitor?->courts()->pluck('courts.id')->all() ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->pullMonitorData($data);
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;
        $this->syncMonitorProfile($user);
    }
}
