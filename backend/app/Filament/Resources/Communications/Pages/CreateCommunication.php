<?php

namespace App\Filament\Resources\Communications\Pages;

use App\Filament\Resources\Communications\CommunicationResource;
use App\Http\Controllers\Api\MarketingConsentController;
use App\Mail\BroadcastCommunication;
use App\Models\Communication;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class CreateCommunication extends CreateRecord
{
    protected static string $resource = CommunicationResource::class;

    /** Fino a questo numero di destinatari l'invio parte subito, senza aspettare il cron. */
    private const IMMEDIATE_LIMIT = 30;

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    public function getTitle(): string
    {
        return 'Nuova comunicazione';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $selected = $data['type'] === Communication::TYPE_SERVICE
            && ($data['audience'] ?? Communication::AUDIENCE_ALL) === Communication::AUDIENCE_SELECTED;

        $recipients = Communication::recipients($data['type'], $selected ? ($data['user_ids'] ?? []) : null);

        $record = Communication::create([
            'type' => $data['type'],
            'audience' => $selected ? Communication::AUDIENCE_SELECTED : Communication::AUDIENCE_ALL,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'recipients_count' => $recipients->count(),
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);

        // Pochi destinatari: partono subito dopo il salvataggio. Tanti: restano nella coda e le
        // spedisce il cron a gruppi (centinaia di invii in una volta sola rischiano di bloccarsi).
        $immediate = $recipients->count() <= self::IMMEDIATE_LIMIT;

        foreach ($recipients as $recipient) {
            $unsubscribeUrls = $record->isMarketing()
                ? MarketingConsentController::unsubscribeUrls($recipient['user'])
                : null;

            $mail = new BroadcastCommunication($record->subject, $record->body, $unsubscribeUrls);

            if ($immediate) {
                $mail->onConnection('deferred');
            }

            Mail::to($recipient['email'])->send($mail);
        }

        return $record;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Comunicazione inviata')
            ->body($this->record->recipients_count.' email in coda per l\'invio.');
    }
}
