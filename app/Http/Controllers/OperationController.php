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
    if (!Auth::user()->isAdmin()) {
        return response()->json(['message' => 'No tienes permiso para crear cierres.'], 403);
    }

    // Total commission is always 5%, split equally among all parties:
    // N advisors + 1 company → each gets 5% / (N + 1)
    $amount = $request->amount ?? 0;
    $sellers = ($request->has('sellers') && is_array($request->sellers)) ? $request->sellers : [];
    $numSellers = count($sellers);
    $numParties = $numSellers + 1; // advisors + company
    $eachPartyPct = round(5 / $numParties, 4);
    $eachPartyAmt = round($amount * $eachPartyPct / 100, 2);

    // 1. Crear la Operación
    $operation = Operation::create(array_merge(
        $request->only(['type', 'property_id', 'amount', 'start_date', 'end_date', 'notes']),
        [
            'company_commission_percentage' => $eachPartyPct,
            'company_commission_amount'     => $eachPartyAmt,
        ]
    ));

    // Guardar buyers (Relación muchos a muchos en Operation)
    if ($request->has('buyers')) {
        $operation->clients()->sync($request->buyers);
    }

    // Guardar sellers con comisiones iguales
    if ($numSellers > 0) {
        $syncData = [];
        foreach ($sellers as $sellerId) {
            $syncData[$sellerId] = [
                'commission_percentage' => $eachPartyPct,
                'commission_amount'     => $eachPartyAmt,
            ];
        }
        $operation->sellers()->sync($syncData);
    }

    // *************************************************************************
    // LOGICA PARA VENTA (Crear registro en tabla Sales)
    // *************************************************************************
    if ($operation->type == 'venta') {
        Sale::create([
            'property_id'  => $operation->property_id,
            'buyer_id'     => $request->buyers[0] ?? null,
            'seller_id'    => $request->sellers[0] ?? null,
            'total_amount' => $operation->amount,
            'date'         => now(),
            'notes'        => $request->notes
        ]);
    }

    // *************************************************************************
    // LOGICA PARA EXCLUSIVIDAD (Generar PDF)
    // *************************************************************************
    $pdfUrl = null;

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

        $pdf = Pdf::loadView('pdf.exclusividad', $data);
        $fileName = 'contrato_exclusividad_' . $operation->id . '.pdf';
        $filePath = 'public/contracts/' . $fileName; 
        
        Storage::put($filePath, $pdf->output());

        $operation->update(['contract_path' => $fileName]);

        $pdfUrl = Storage::url('contracts/' . $fileName);
    }

    return response()->json([
        'message' => 'Operation created successfully.',
        'data'    => $operation,
        'pdf_url' => $pdfUrl,
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
        $users = User::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($u) => [
                'id'    => $u->id,
                'value' => trim($u->first_name . ' ' . $u->last_name),
            ]);

        return response()->json([
            'properties' => Property::select('id', 'title as value', 'price')->get(),
            'clients'    => Client::select('id', 'name as value')->get(),
            'users'      => $users,
        ]);
    }
}
