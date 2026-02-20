<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Core\Auth\Traits\Attribute\UserAttribute;
use App\Models\Core\Auth\Traits\Boot\UserBootTrait;
use App\Models\Core\Auth\Traits\Method\HasRoles;
use App\Models\Core\Auth\Traits\Method\UserMethod;
use App\Models\Core\Auth\Traits\Method\UserStatus;
use App\Models\Core\Auth\Traits\Relationship\UserRelationship;
use App\Models\Core\Auth\Traits\Rules\UserRules;
use App\Models\Core\Auth\Traits\Scope\UserScope;
use Spatie\Activitylog\Traits\CausesActivity;
use Altek\Eventually\Eventually;
use App\Models\Core\Auth\User;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{

    use UserAttribute,
        UserMethod,
        UserRelationship,
        UserScope,
        HasRoles,
        UserRules,
        UserBootTrait,
        Eventually,
        Notifiable,
        CausesActivity,
        UserStatus,
        HasFactory;

    protected $fillable = [
        'type',
        'property_id',
        'amount',
        'start_date',
        'end_date',
        'notes',
        'company_commission_percentage',
        'company_commission_amount',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'operation_client');
    }

    public function sellers()
    {
        return $this->belongsToMany(User::class, 'operation_user')
            ->withPivot('commission_percentage', 'commission_amount');
    }
}
