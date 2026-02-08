<?php

use App\Models\Operation;
use App\Models\Property;
use Faker\Generator as Faker;

$factory->define(Operation::class, function (Faker $faker) {
    return [
        'type' => $faker->randomElement(['venta', 'reserva', 'exclusividad']),
        'property_id' => factory(Property::class),
        'amount' => $faker->randomFloat(2, 5000, 200000),
        'start_date' => $faker->dateTimeBetween('-1 year', 'now'),
        'end_date' => $faker->optional()->dateTimeBetween('now', '+1 year'),
        'notes' => $faker->optional()->sentence()
    ];
});
