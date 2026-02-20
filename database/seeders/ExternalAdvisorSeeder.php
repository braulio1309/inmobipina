<?php

namespace Database\Seeders;

use App\Models\Core\Auth\User;
use App\Models\Core\Status;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ExternalAdvisorSeeder extends Seeder
{
    public function run()
    {
        $status = Status::findByNameAndType('status_active', 'user');

        User::firstOrCreate(
            ['email' => 'externo@inmobipina.com'],
            [
                'first_name' => 'Asesor',
                'last_name'  => 'Externo',
                'password'   => Hash::make(Str::random(24)),
                'status_id'  => $status ? $status->id : 1,
            ]
        );
    }
}
