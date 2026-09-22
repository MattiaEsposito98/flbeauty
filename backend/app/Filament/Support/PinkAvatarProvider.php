<?php

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class PinkAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(function (string $segment): string {
                $letters = preg_replace('/^[^\p{L}\p{N}]+/u', '', $segment);

                return filled($letters) ? mb_strtoupper(mb_substr($letters, 0, 1)) : '';
            })
            ->filter()
            ->take(2)
            ->join('');

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">
                <circle cx="20" cy="20" r="20" fill="#B76E79" />
                <text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif" font-size="16" font-weight="600" fill="#FFFFFF">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
