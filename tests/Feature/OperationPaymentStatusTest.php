<?php

namespace Tests\Feature;

use App\Models\Operation;
use App\Models\Property;
use App\Models\Core\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_rental_payment_status_history(): void
    {
        $user = User::factory()->create();
        $property = Property::create([
            'title' => 'Casa test',
            'address' => 'Dirección test',
            'status' => 'Alquilado',
            'type' => 'casa',
            'created_by' => $user->id,
        ]);

        $operation = Operation::create([
            'type' => 'alquiler',
            'property_id' => $property->id,
            'amount' => 1500,
            'start_date' => '2026-01-01',
            'fecha_corte' => '2026-12-31',
            'payment_frequency' => 'mensual',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/operations/{$operation->id}/payment-status", [
            'status' => 'on_time',
            'note' => 'Pago registrado desde prueba',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('rental_payment_histories', [
            'operation_id' => $operation->id,
            'status' => 'on_time',
            'note' => 'Pago registrado desde prueba',
        ]);
    }
}
