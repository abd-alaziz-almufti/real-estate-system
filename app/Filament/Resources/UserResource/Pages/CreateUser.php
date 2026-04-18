<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
      protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
      public function mount(): void
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->company?->canAddUser()) {
            Notification::make()
                ->title('Limit Reached')
                ->body('Your plan does not allow adding more users.')
                ->danger()
                ->persistent()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
            return;
        }

        parent::mount();
    }
}
