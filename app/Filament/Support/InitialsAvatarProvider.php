<?php

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Renders initials as an inline SVG. Replaces Filament's default ui-avatars.com provider so
 * user names are never sent to a third-party service (data protection).
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $name = $record instanceof Authenticatable ? Filament::getUserName($record) : (string) $record->getAttribute('name');

        $initials = Str::of($name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        $palette = ['#4f46e5', '#0891b2', '#059669', '#d97706', '#db2777', '#7c3aed'];
        $color = $palette[crc32($name) % count($palette)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="'.$color.'"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="system-ui, sans-serif" font-size="26" font-weight="600" fill="#ffffff">'
            .e($initials ?: '?')
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
