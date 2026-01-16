<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operation;
use App\Filters\Common\Auth\OperationFilter as AppUserFilter;
use App\Filters\Core\OperationFilter;
use App\Models\Client;
use App\Models\Core\Auth\User;
use App\Models\Property;
use App\Services\Core\Auth\OperationService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;


class OperationController extends Controller
{

    public function __construct(OperationService $Transaction, OperationFilter $filter)
    {
        $this->service = $Transaction;
        $this->filter = $filter;
    }

    public function listado()
    {
        return (new AppUserFilter(
        $this->service->with('sellers')
            ->filters($this->filter)
            ->latest()
    ))
    ->filter()
    ->paginate(request()->get('per_page', 10))
    ->through(function ($item) {
        // Agregar sellers_names
        $item->sellers_names = $item->sellers
            ->map(fn($s) => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')))
            ->filter()
            ->implode(', ');

        return $item;
    });

    }

    public function create(Request $request)
    {
        $operation = Operation::create($request->all());

        if ($request->has('buyers')) {
            $operation->clients()->sync($request->buyers);
        }

        // Guardar sellers
        if ($request->has('sellers')) {
            $operation->sellers()->sync($request->sellers);
        }
        
        if ($operation->type == 'exclusividad'){
            $propietario = $operation->clients->first(); // Asumo que el seller es el propietario
            $data = [
                'propietario' => [
                    'nombre' => $propietario->name ?? 'Nombre del Propietario',
                    'ci' => $propietario->ci ?? 'V-12.345.678',
                    'rif' => '123564',
                    'email' => $propietario->email,
                    'phone' => $propietario->phone
                ],
                'inmueble' => [
                    'precio_numeros' => number_format($operation->price, 2) ?? '0.00',
                    // ... otros datos del inmueble
                ],
                'fecha_contrato' => now()->locale('es')->isoFormat('DD \d\e MMMM \d\e YYYY'),
                'property' => [
                    'price' => $operation->property->price,
                    'square_meters' =>$operation->property->square_meters,
                    'address' => $operation->property->address,
                ]
            ];
            // *************************************************************************

            // 3. Generar y Guardar el PDF
            $pdf = Pdf::loadView('pdf.exclusividad', $data);
            
            // Define la ruta y el nombre del archivo
            $fileName = 'contrato_exclusividad_' . $operation->id . '.pdf';
            $filePath = 'public/contracts/' . $fileName; // Guarda en storage/app/public/contracts
            
            // Guarda el archivo en el disco 'public'
            Storage::put($filePath, $pdf->output());

            // Opcional: Guardar la ruta del contrato en la base de datos de la operación
            $operation->update(['contract_path' => $fileName]);

        
            
            // 4. Devolver la respuesta al frontend (incluyendo la URL de descarga)
            return response()->json([
                'message' => 'Operation created successfully.',
                'data' => $operation,
                // Genera la URL pública para la descarga
                'pdf_url' => Storage::url('contracts/' . $fileName), 
            ], 201);
        }
        
    }

    public function edit(Request $request, $id)
    {

        $Operation = Operation::where('id', $id)->first();
        $Operation->update($request->all());

        return created_responses('Transaction');
    }

    public function show(Operation $Operation)
    {
        return response()->json($Operation);
    }

    public function formData()
    {
        return response()->json([
            'properties' => Property::select('id', 'title as value', 'price')->get(),
            'clients'    => Client::select('id', 'name as value')->get(),
            'users'      => User::select('id', 'first_name as value')->get(),
        ]);
    }
}
