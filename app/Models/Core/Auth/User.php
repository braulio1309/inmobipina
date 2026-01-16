<?php

namespace App\Models\Core\Auth;

use Altek\Eventually\Eventually;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Commission;
use App\Models\Core\Auth\Traits\Attribute\UserAttribute;
use App\Models\Core\Auth\Traits\Boot\UserBootTrait;
use App\Models\Core\Auth\Traits\Method\HasRoles;
use App\Models\Core\Auth\Traits\Method\UserMethod;
use App\Models\Core\Auth\Traits\Method\UserStatus;
use App\Models\Core\Auth\Traits\Relationship\UserRelationship;
use App\Models\Core\Auth\Traits\Rules\UserRules;
use App\Models\Core\Auth\Traits\Scope\UserScope;
use App\Models\Exclusivity;
use App\Models\Property;
use App\Models\PropertyAdvisor;
use App\Models\Sale;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;


class User extends BaseUser implements HasLocalePreference
{
    protected static $logAttributes = [
        'first_name',
        'last_name',
        'email'
    ];

    use UserAttribute,
        UserMethod,
        UserRelationship,
        UserScope,
        HasRoles,
        UserRules,
        UserBootTrait,
        LogsActivity,
        Eventually,
        Notifiable,
        CausesActivity,
        UserStatus,
        HasFactory;

    public function preferredLocale(): ?string
    {
        return app()->getLocale() ?? 'en';
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $class_alias_array = explode('\\', get_called_class());
        $class_name = strtolower(end($class_alias_array));

        return trans('default.log_description_message', [
            'model' => trans('default.' . $class_name),
            'event' => trans('default.' . $eventName)
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'created_by');
    }

    public function propertyAdvisors()
    {
        return $this->hasMany(PropertyAdvisor::class);
    }

    public function exclusivities()
    {
        return $this->hasMany(Exclusivity::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'seller_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }
}
