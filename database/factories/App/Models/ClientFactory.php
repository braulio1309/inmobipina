<?php

use App\Models\Client;
use Faker\Generator as Faker;

$factory->define(Client::class, function (Faker $faker) {
    return [
        'name' => $faker->name(),
        'email' => $faker->unique()->safeEmail(),
        'phone' => $faker->phoneNumber(),
        'ci' => $faker->optional()->numerify('V-########'),
        'address' => $faker->optional()->address()
    ];
});
