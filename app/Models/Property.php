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


class Property extends Model
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
        'agent_id', 'title', 'description', 'price',
        'bathrooms', 'bedrooms', 'square_meters', 'address', 'type', 'type_sale', 'status',
        'map_lat', 'map_lng', 'exclusivity', 'created_by', 'approved_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function advisors()
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
        return $this->hasMany(Sale::class);
    }
}
