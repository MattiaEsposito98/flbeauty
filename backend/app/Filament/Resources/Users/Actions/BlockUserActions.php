<?php

namespace App\Filament\Resources\Users\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Blocca/sblocca un cliente, dalla scheda utente e dall'elenco.
 * Il blocco chiude gli accessi aperti e impedisce di accedere di nuovo;
 * gli ordini aperti si possono annullare nello stesso passaggio (lo stock torna
 * disponibile da solo, vedi Order::booted()).
 */
class BlockUserActions
{
    public static function block(): Action
    {
        return Action::make('blockUser')
            ->label('Blocca account')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(fn (User $record) => ! $record->isBlocked())
            ->modalHeading(fn (User $record) => 'Bloccare '.$record->name.'?')
            ->modalDescription(function (User $record) {
                $active = $record->activeOrders()->get();

                $text = 'Il cliente verrà disconnesso e non potrà più accedere né ordinare. Puoi sbloccarlo quando vuoi.';

                if ($active->isEmpty()) {
                    return $text;
                }

                $list = $active
                    ->map(fn ($order) => '#'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)
                        .' — '.($order->status === 'nuovo' ? 'in attesa di pagamento' : 'in lavorazione')
                        .' — '.number_format((float) $order->total, 2, ',', '.').' €')
                    ->map(fn (string $line) => '<li>'.e($line).'</li>')
                    ->implode('');

                return new HtmlString(
                    e($text)
                    .'<p style="margin-top:.75rem"><strong>Ha '.$active->count().' ordini ancora aperti</strong>, che tengono da parte i prodotti in magazzino:</p>'
                    .'<ul style="margin:.5rem 0 0 1.25rem;list-style:disc">'.$list.'</ul>'
                );
            })
            ->modalSubmitActionLabel('Blocca account')
            ->schema([
                Toggle::make('cancel_orders')
                    ->label('Annulla anche gli ordini aperti')
                    ->helperText('I prodotti tornano subito disponibili. Per ogni ordine il cliente riceve la normale email di cambio stato.')
                    ->default(true)
                    ->visible(fn (User $record) => $record->activeOrders()->exists()),
                Textarea::make('reason')
                    ->label('Motivo (solo per te, il cliente non lo vede)')
                    ->placeholder('Es. ordini fasulli')
                    ->rows(2)
                    ->maxLength(255),
            ])
            ->action(function (User $record, array $data) {
                $cancelled = $record->block($data['reason'] ?? null, (bool) ($data['cancel_orders'] ?? false));

                Notification::make()
                    ->success()
                    ->title('Account bloccato')
                    ->body($cancelled > 0 ? $cancelled.' ordini annullati, prodotti rimessi in magazzino.' : null)
                    ->send();
            });
    }

    public static function unblock(): Action
    {
        return Action::make('unblockUser')
            ->label('Sblocca account')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->visible(fn (User $record) => $record->isBlocked())
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => 'Sbloccare '.$record->name.'?')
            ->modalDescription('Il cliente potrà accedere e ordinare di nuovo.')
            ->action(function (User $record) {
                $record->unblock();

                Notification::make()->success()->title('Account sbloccato')->send();
            });
    }
}
