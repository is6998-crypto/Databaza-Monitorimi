<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

/**
 * Every role/permission assignment change is written to the audit log (log name "permissions").
 */
class AuditPermissionChanges
{
    public function roleAttached(RoleAttachedEvent $event): void
    {
        $this->log($event->model, 'role_attached', ['roles' => $this->names($event->rolesOrIds, 'role')]);
    }

    public function roleDetached(RoleDetachedEvent $event): void
    {
        $this->log($event->model, 'role_detached', ['roles' => $this->names($event->rolesOrIds, 'role')]);
    }

    public function permissionAttached(PermissionAttachedEvent $event): void
    {
        $this->log($event->model, 'permission_attached', ['permissions' => $this->names($event->permissionsOrIds, 'permission')]);
    }

    public function permissionDetached(PermissionDetachedEvent $event): void
    {
        $this->log($event->model, 'permission_detached', ['permissions' => $this->names($event->permissionsOrIds, 'permission')]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(Model $subject, string $event, array $properties): void
    {
        $causer = auth()->user();

        activity('permissions')
            ->performedOn($subject)
            ->causedBy($causer instanceof User ? $causer : null)
            ->event($event)
            ->withProperties($properties)
            ->log(str_replace('_', ' ', ucfirst($event)));
    }

    /**
     * @return list<string>
     */
    private function names(mixed $items, string $kind): array
    {
        $items = is_iterable($items) ? collect($items) : collect([$items]);
        $model = $kind === 'role' ? config('permission.models.role') : config('permission.models.permission');

        return $items->map(function (mixed $item) use ($model): string {
            if ($item instanceof Model) {
                return (string) $item->getAttribute('name');
            }

            if (is_numeric($item)) {
                return (string) ($model::query()->whereKey($item)->value('name') ?? $item);
            }

            return (string) $item;
        })->values()->all();
    }
}
