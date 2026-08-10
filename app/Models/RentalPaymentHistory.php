<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalPaymentHistory extends Model
{
    protected $table = 'rental_payment_histories';

    protected $fillable = [
        'operation_id',
        'status',
        'note',
        'created_by',
    ];

    public function operation()
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Core\Auth\User::class, 'created_by');
    }
}
