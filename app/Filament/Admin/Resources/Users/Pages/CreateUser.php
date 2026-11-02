<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use SyncsMonitorProfile;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->pullMonitorData($data);
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;
        $this->syncMonitorProfile($user);
    }
}
