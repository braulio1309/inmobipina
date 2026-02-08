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
use App\Models\Sale; 

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
    // 1. Crear la Operación
    $operation = Operation::create($request->all());

    // Guardar buyers (Relación muchos a muchos en Operation)
    if ($request->has('buyers')) {
        $operation->clients()->sync($request->buyers);
    }

    // Guardar sellers (Relación muchos a muchos en Operation)
    if ($request->has('sellers')) {
        $operation->sellers()->sync($request->sellers);
    }

    // *************************************************************************
    // LOGICA PARA VENTA (Crear registro en tabla Sales)
    // *************************************************************************
    if ($operation->type == 'venta') {
        Sale::create([
            'property_id'  => $operation->property_id, // O $request->property_id
            'buyer_id'     => $request->buyers[0] ?? null, // Tomamos el primer comprador del array
            'seller_id'    => $request->sellers[0] ?? null, // Tomamos el primer vendedor del array
            'total_amount' => $operation->price, // Asumimos que el precio de la operación es el monto total
            'date'         => now(), // O $request->date si viene en el request
            'notes'        => $request->notes
        ]);
    }

    // *************************************************************************
    // LOGICA PARA EXCLUSIVIDAD (Generar PDF)
    // *************************************************************************
    $pdfUrl = null; // Variable para almacenar la URL si se genera PDF

    if ($operation->type == 'exclusividad') {
        $propietario = $operation->clients->first(); 
        
        $data = [
            'propietario' => [
                'nombre' => $propietario->name ?? 'Nombre del Propietario',
                'ci'     => $propietario->ci ?? 'V-12.345.678',
                'rif'    => '123564',
                'email'  => $propietario->email,
                'phone'  => $propietario->phone
            ],
            'inmueble' => [
                'precio_numeros' => number_format($operation->price, 2) ?? '0.00',
            ],
            'fecha_contrato' => now()->locale('es')->isoFormat('DD \d\e MMMM \d\e YYYY'),
            'property' => [
                'price'         => $operation->property->price,
                'square_meters' => $operation->property->square_meters,
                'address'       => $operation->property->address,
            ]
        ];

        // Generar y Guardar el PDF
        $pdf = Pdf::loadView('pdf.exclusividad', $data);
        $fileName = 'contrato_exclusividad_' . $operation->id . '.pdf';
        $filePath = 'public/contracts/' . $fileName; 
        
        Storage::put($filePath, $pdf->output());

        // Actualizar operación con la ruta
        $operation->update(['contract_path' => $fileName]);

        // Generar URL para la respuesta
        $pdfUrl = Storage::url('contracts/' . $fileName);
    }

    // 4. Devolver la respuesta al frontend (Unificada)
    return response()->json([
        'message' => 'Operation created successfully.',
        'data'    => $operation,
        'pdf_url' => $pdfUrl, // Será null si es venta, o la URL si es exclusividad
    ], 201);
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
