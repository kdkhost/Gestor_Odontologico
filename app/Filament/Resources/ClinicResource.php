<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

abstract class ClinicResource extends Resource
{
    protected static string $permissionPrefix = 'dashboard';

    public static function canViewAny(): bool
    {
        return app()->runningInConsole() || auth()->user()?->can(static::$permissionPrefix.'.view') === true;
    }

    public static function canCreate(): bool
    {
        return app()->runningInConsole() || auth()->user()?->can(static::$permissionPrefix.'.create') === true;
    }

    public static function canEdit($record): bool
    {
        return app()->runningInConsole() || auth()->user()?->can(static::$permissionPrefix.'.update') === true;
    }

    public static function canDelete($record): bool
    {
        return app()->runningInConsole() || auth()->user()?->can(static::$permissionPrefix.'.delete') === true;
    }

    public static function canDeleteAny(): bool
    {
        return static::canDelete(null);
    }

    public static function canForceDelete($record): bool
    {
        return static::canDelete($record);
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canDeleteAny();
    }

    public static function canRestore($record): bool
    {
        return static::canEdit($record);
    }

    public static function canRestoreAny(): bool
    {
        return static::canCreate();
    }
}
