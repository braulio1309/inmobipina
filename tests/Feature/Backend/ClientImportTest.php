<?php

namespace Tests\Feature\Backend;

use App\Imports\ClientImport;
use App\Models\Client;
use App\Models\Core\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientImportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_import_matches_advisor_when_excel_name_is_reversed()
    {
        $admin = $this->loginAsAdmin();
        $advisor = User::query()->create([
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan.perez@example.com',
            'password' => Hash::make('secret123'),
            'status_id' => 1,
        ]);

        $this->actingAs($admin);

        $import = new ClientImport();
        $import->collection(new Collection([
            collect([
                'nombre_del_cliente' => 'Cliente Demo',
                'as_asignado' => 'Perez Juan',
            ]),
        ]));

        $this->assertSame($advisor->id, Client::firstOrFail()->assigned_to);
    }

    /** @test */
    public function admin_import_matches_advisor_when_excel_name_has_accents_or_punctuation()
    {
        $admin = $this->loginAsAdmin();
        $advisor = User::query()->create([
            'first_name' => 'Jose',
            'last_name' => 'Nunez',
            'email' => 'jose.nunez@example.com',
            'password' => Hash::make('secret123'),
            'status_id' => 1,
        ]);

        $this->actingAs($admin);

        $import = new ClientImport();
        $import->collection(new Collection([
            collect([
                'nombre_del_cliente' => 'Cliente Demo 2',
                'as_asignado' => 'Núñez, José',
            ]),
        ]));

        $this->assertSame($advisor->id, Client::firstOrFail()->assigned_to);
    }
}