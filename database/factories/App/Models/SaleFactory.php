<?php

use App\Models\Client;
use App\Models\Core\Auth\User;
use App\Models\Property;
use App\Models\Sale;
use Faker\Generator as Faker;

$factory->define(Sale::class, function (Faker $faker) {
    return [
        'property_id' => factory(Property::class),
        'buyer_id' => factory(Client::class),
        'seller_id' => factory(User::class),
        'total_amount' => $faker->randomFloat(2, 10000, 500000),
        'date' => $faker->dateTimeBetween('-1 year', 'now'),
        'notes' => $faker->optional()->sentence()
    ];
});
