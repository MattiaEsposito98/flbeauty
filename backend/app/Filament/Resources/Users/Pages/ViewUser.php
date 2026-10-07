<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Users\Actions\BlockUserActions;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use HasBackToListAction;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
            BlockUserActions::block()->record($this->record),
            BlockUserActions::unblock()->record($this->record),
            // Per i clienti che non ricevono l'email di verifica (spam, casella
            // piena) e chiedono aiuto su WhatsApp.
            Action::make('verifyEmail')
                ->label('Segna email come verificata')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => ! $this->record->hasVerifiedEmail())
                ->requiresConfirmation()
                ->modalDescription('Da usare solo se l\'indirizzo email appartiene davvero a questa persona, ad esempio se te l\'ha confermato su WhatsApp.')
                ->action(function () {
                    $this->record->markEmailAsVerified();
                    Notification::make()->success()->title('Email segnata come verificata')->send();
                }),
        ];
    }
}
