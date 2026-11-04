<?php

namespace App\Filament\Research\Pages\Auth;

use App\Enums\ResearcherStatus;
use App\Enums\Role;
use App\Models\ResearcherProfile;
use App\Models\User;
use App\Notifications\ResearcherRegistered;
use App\Support\Permissions;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use SensitiveParameter;

/**
 * Researcher self-registration. The account stays "pending" until an administrator approves it.
 */
class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            TextInput::make('institution')->label(__('research.register.institution'))->required()->maxLength(255),
            TextInput::make('position')->label(__('research.register.position'))->maxLength(255),
            Textarea::make('research_purpose')->label(__('research.register.purpose'))->helperText(__('research.register.purpose_help'))->required()->minLength(30)->maxLength(3000)->rows(4),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        /** @var User $user */
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => app()->getLocale(),
        ]);

        $user->assignRole(Role::Researcher->value);

        ResearcherProfile::query()->create([
            'user_id' => $user->id,
            'institution' => $data['institution'],
            'position' => $data['position'] ?? null,
            'research_purpose' => $data['research_purpose'],
            'status' => ResearcherStatus::Pending,
        ]);

        $approvers = User::query()->where('is_active', true)->get()->filter(fn (User $admin): bool => $admin->checkPermissionTo(Permissions::APPROVE_RESEARCHERS));
        Notification::send($approvers, new ResearcherRegistered($user));

        return $user;
    }
}
