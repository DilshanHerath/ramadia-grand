<?php

namespace App\Filament\Resources\Invites\Pages;

use App\Filament\Resources\Invites\InviteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvite extends CreateRecord
{
    protected static string $resource = InviteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
