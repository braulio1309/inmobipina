<?php

namespace App\Models;

use App\Models\Core\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exclusivity extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'user_id', 'start_date', 'end_date', 'file_path'];


    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
