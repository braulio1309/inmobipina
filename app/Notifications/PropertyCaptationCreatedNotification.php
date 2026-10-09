<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Notifications\Notification;

class PropertyCaptationCreatedNotification extends Notification
{
    private Property $property;

    private ?int $notifierId;

    public function __construct(Property $property, ?int $notifierId)
    {
        $this->property = $property;
        $this->notifierId = $notifierId;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $propertyName = $this->property->title ?: $this->property->address ?: 'Propiedad #' . $this->property->id;

        return [
            'message' => 'Nueva captación registrada: ' . $propertyName,
            'name' => 'Sistema',
            'url' => '/properties',
            'notifier_id' => $this->notifierId,
            'event_type' => 'property_captation_created',
            'property_id' => $this->property->id,
        ];
    }
}
