<?php

namespace App\Notifications;

use App\Models\Operation;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;

class RentalExpirationNotification extends Notification
{
    private Operation $operation;

    public function __construct(Operation $operation)
    {
        $this->operation = $operation;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $propertyName = optional($this->operation->property)->title
            ?: ($this->operation->external_property_title ?: 'Operación #' . $this->operation->id);
        $expirationDate = Carbon::parse($this->operation->fecha_corte);
        $daysUntilExpiration = Carbon::today()->diffInDays($expirationDate, false);
        $relativeExpiration = match ($daysUntilExpiration) {
            0 => 'vence hoy',
            1 => 'vence mañana',
            default => 'vence en ' . $daysUntilExpiration . ' días',
        };

        return [
            'message' => 'El alquiler de ' . $propertyName . ' vence el '
                . $expirationDate->format('d/m/Y') . ' (' . $relativeExpiration . ').',
            'name' => 'Sistema',
            'url' => '/operations',
            'notifier_id' => null,
            'event_type' => 'rental_expiring',
            'operation_id' => $this->operation->id,
            'expires_on' => $expirationDate->toDateString(),
        ];
    }
}
