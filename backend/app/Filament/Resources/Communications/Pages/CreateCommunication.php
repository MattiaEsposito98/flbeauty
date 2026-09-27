<?php

namespace App\Filament\Resources\Communications\Pages;

use App\Filament\Resources\Communications\CommunicationResource;
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

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    public function getTitle(): string
    {
        return 'Nuova comunicazione';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $recipients = Communication::recipientEmails();

        $record = Communication::create([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'recipients_count' => $recipients->count(),
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);

        foreach ($recipients as $email) {
            Mail::to($email)->send(new BroadcastCommunication($record->subject, $record->body));
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
