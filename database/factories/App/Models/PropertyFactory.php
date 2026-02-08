<?php

use App\Models\Core\Auth\User;
use App\Models\Property;
use Faker\Generator as Faker;

$factory->define(Property::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'description' => $faker->paragraph(),
        'price' => $faker->randomFloat(2, 50000, 1000000),
        'bathrooms' => $faker->numberBetween(1, 5),
        'bedrooms' => $faker->numberBetween(1, 6),
        'square_meters' => $faker->randomFloat(2, 50, 500),
        'address' => $faker->address(),
        'type' => $faker->randomElement(['casa', 'apartamento', 'local', 'terreno']),
        'type_sale' => $faker->randomElement(['venta', 'alquiler', 'ambos']),
        'status' => $faker->randomElement(['disponible', 'vendida', 'reservada']),
        'created_by' => factory(User::class),
        'approved_by' => null
    ];
});
