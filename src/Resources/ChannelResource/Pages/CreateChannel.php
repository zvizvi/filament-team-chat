<?php

namespace Filament\TeamChat\Resources\ChannelResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\TeamChat\Resources\ChannelResource;

class CreateChannel extends CreateRecord
{
    protected static string $resource = ChannelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->members()->attach(auth()->id(), ['role' => 'owner']);
    }
}
