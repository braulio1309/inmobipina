<?php

namespace App\Services;

use App\Models\Core\Auth\User;
use App\Models\Operation;
use App\Notifications\RentalExpirationNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RentalExpirationNotificationService
{
    private const NOTICE_WINDOW_DAYS = 7;

    public function notifyUpcomingRentals(): void
    {
        if (!Cache::add('rental-expiration-notification-check', true, now()->addMinutes(5))) {
            return;
        }

        $today = Carbon::today();
        $lastNoticeDate = $today->copy()->addDays(self::NOTICE_WINDOW_DAYS);
        $activeUsers = User::active()->get();
        $administrators = $activeUsers->filter(
            fn (User $user) => $user->isAdmin() || $user->isAppAdmin()
        );
        $activeAdvisorIds = User::active()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Asesor'))
            ->pluck('users.id')
            ->all();

        $operations = Operation::with([
            'property:id,title,status',
            'sellers' => fn ($query) => $query->whereIn('users.id', $activeAdvisorIds),
        ])
            ->where('type', 'alquiler')
            ->whereBetween('fecha_corte', [$today->toDateString(), $lastNoticeDate->toDateString()])
            ->where(function ($query) {
                $query->whereHas('property', fn ($propertyQuery) => $propertyQuery->where('status', 'Alquilado'))
                    ->orWhereNull('property_id');
            })
            ->get();

        foreach ($operations as $operation) {
            $expirationDate = Carbon::parse($operation->fecha_corte)->toDateString();
            $alreadyNotified = DB::table('notifications')
                ->where('type', RentalExpirationNotification::class)
                ->where('data->event_type', 'rental_expiring')
                ->where('data->operation_id', $operation->id)
                ->where('data->expires_on', $expirationDate)
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $recipients = $administrators
                ->concat($operation->sellers)
                ->unique('id')
                ->values();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new RentalExpirationNotification($operation));
            }
        }
    }
}
