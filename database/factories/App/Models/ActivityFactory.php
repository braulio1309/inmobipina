<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\Core\Auth\User;
use App\Models\Property;
use Faker\Generator as Faker;

$factory->define(Activity::class, function (Faker $faker) {
    return [
        'user_id' => factory(User::class),
        'client_id' => factory(Client::class),
        'property_id' => factory(Property::class),
        'type' => $faker->randomElement(['demostración', 'captación', 'venta', 'alquiler', 'reserva']),
        'description' => $faker->sentence(),
        'result' => $faker->optional()->sentence(),
        'date' => $faker->dateTimeBetween('-1 year', 'now')
    ];
});
