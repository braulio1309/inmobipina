<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'weight_kg',
        'height_cm',
        'activity_level',
        'goal',
        'start_date',
        'allergies',
        'preferences',
        'observations',
        'tmb_kcal',
        'total_kcal',
        'target_kcal',
        'macros',
        'meal_options',
        'criteria',
        'free_meal_rules',
        'supplements',
        'coach_notes',
        'footer',
    ];

    protected $casts = [
        'macros' => 'array',
        'meal_options' => 'array',
        'criteria' => 'array',
        'start_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
