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
        'external_property_title',
        'owner_client_id',
        'buyer_client_id',
        'amount',
        'property_price',
        'start_date',
        'end_date',
        'fecha_cierre',
        'notes',
        'company_commission_percentage',
        'company_commission_amount',
        'reservation_company_commission',
        'contract_path',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'operation_client');
    }

    public function ownerClient()
    {
        return $this->belongsTo(Client::class, 'owner_client_id');
    }

    public function buyerClient()
    {
        return $this->belongsTo(Client::class, 'buyer_client_id');
    }

    public function sellers()
    {
        return $this->belongsToMany(User::class, 'operation_user')
            ->withPivot('commission_percentage', 'commission_amount', 'reservation_commission_amount');
    }
}
