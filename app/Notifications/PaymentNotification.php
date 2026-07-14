<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public \App\Models\Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Receipt: ' . $this->payment->amount . ' - Unit ' . ($this->payment->lease->unit->unit_number ?? ''))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('We have successfully processed your payment.')
            ->line('Amount: ' . $this->payment->amount)
            ->line('Date: ' . $this->payment->payment_date)
            ->line('Thank you for your business!');
    }

    public function toArray(object $notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title('Payment Received')
            ->body("A payment of {$this->payment->amount} was recorded for Unit " . ($this->payment->lease->unit->unit_number ?? ''))
            ->icon('heroicon-o-currency-dollar')
            ->iconColor('success')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(\App\Filament\Resources\PaymentResource::getUrl('view', ['record' => $this->payment->id])),
            ])
            ->getDatabaseMessage();
    }
    public function toBroadcast(object $notifiable): array
    {
        return [
            'message' => "A payment of {$this->payment->amount} was recorded for Unit " . ($this->payment->lease->unit->unit_number ?? '') . ' in Property : ' . ($this->payment->lease->unit->property->name ?? ''),
            'payment_id' => $this->payment->id,
            'url' => \App\Filament\Resources\PaymentResource::getUrl('view', ['record' => $this->payment->id]),
        ];
    }
    // public function toBroadcast(object $notifiable): array
    // {
    //     return new BroadcastMessage([
    //         'message' => "A payment of {$this->payment->amount} was recorded for Unit " . ($this->payment->lease->unit->unit_number ?? '') . ' in Property : ' . ($this->payment->lease->unit->property->name ?? ''),
    //         'payment_id' => $this->payment->id,
    //         'url' => \App\Filament\Resources\PaymentResource::getUrl('view', ['record' => $this->payment->id]),
    //     ])->onQueue('notifications');
    // }
}
