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
use App\Exports\OperationExport;
use Maatwebsite\Excel\Facades\Excel;

class OperationController extends Controller
{

    public function __construct(OperationService $Transaction, OperationFilter $filter)
    {
        $this->service = $Transaction;
        $this->filter = $filter;
    }

    public function listado()
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $operations */
        $operations = (new AppUserFilter(
            $this->service->with(['sellers', 'property'])
                ->filters($this->filter)
                ->latest()
        ))
            ->filter()
            ->paginate(request()->get('per_page', 10));

        $operations->getCollection()->transform(function ($item) {
            $item->sellers_names = $item->sellers
                ->map(fn ($s) => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')))
                ->filter()
                ->implode(', ');

            $item->commission_details = $item->sellers->map(function ($s) {
                $name = trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));

                return [
                    'name' => $name ?: ($s->email ?? '—'),
                    'percentage' => $s->pivot->commission_percentage ?? 0,
                    'amount' => $s->pivot->commission_amount ?? 0,
                ];
            })->values()->toArray();

            $item->property_title = $item->property ? $item->property->title : '';
            $item->contract_url = $item->contract_path
                ? Storage::url('contracts/' . $item->contract_path)
                : null;

            return $item;
        });

        return $operations;
    }

    public function create(Request $request)
{
    /** @var \App\Models\Core\Auth\User|null $authUser */
    $authUser = Auth::user();

    if (!$authUser || !$authUser->isAdmin()) {
        return response()->json(['message' => 'No tienes permiso para crear cierres.'], 403);
    }

    // Total commission is always 5%: company always gets 2.5%, advisors share the remaining 2.5%
    $amount = $request->amount ?? 0;
    $sellers = ($request->has('sellers') && is_array($request->sellers)) ? $request->sellers : [];
    $numSellers = count($sellers);
    $companyPct = 2.5;
    $companyAmt = round($amount * $companyPct / 100, 2);
    $eachAdvisorPct = $numSellers > 0 ? round(2.5 / $numSellers, 4) : 0;

    // Use per-seller percentages if provided
    $sellersWithPct = ($request->has('sellers_commissions') && is_array($request->sellers_commissions))
        ? $request->sellers_commissions
        : [];

    // 1. Crear la Operación
    $operation = Operation::create(array_merge(
        $request->only(['type', 'property_id', 'amount', 'property_price', 'start_date', 'end_date', 'notes']),
        [
            'company_commission_percentage' => $companyPct,
            'company_commission_amount'     => $companyAmt,
        ]
    ));

    // Guardar buyers (Relación muchos a muchos en Operation)
    if ($request->has('buyers')) {
        $operation->clients()->sync($request->buyers);
    }

    // Guardar sellers con comisiones
    if ($numSellers > 0) {
        $syncData = [];
        foreach ($sellers as $sellerId) {
            $customPct = null;
            foreach ($sellersWithPct as $sc) {
                if ((string)($sc['id'] ?? '') === (string)$sellerId) {
                    $customPct = $sc['percentage'] ?? null;
                    break;
                }
            }
            $pct = $customPct !== null ? (float)$customPct : $eachAdvisorPct;
            $amt = round($amount * $pct / 100, 2);
            $syncData[$sellerId] = [
                'commission_percentage' => $pct,
                'commission_amount'     => $amt,
            ];
        }
        $operation->sellers()->sync($syncData);
    }

    // Actualizar status de la propiedad según tipo de operación
    if ($operation->type === 'reserva') {
        Property::where('id', $operation->property_id)->update(['status' => 'Reservado']);
    } elseif ($operation->type === 'venta' || $operation->type === 'traspaso') {
        Property::where('id', $operation->property_id)->update(['status' => 'Vendido']);
    }

    // *************************************************************************
    // LOGICA PARA VENTA Y TRASPASO (Crear registro en tabla Sales)
    // *************************************************************************
    if ($operation->type == 'venta' || $operation->type == 'traspaso') {
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
        $property = $operation->property;
        $exclusivity = $property ? $property->exclusivities()->latest()->first() : null;
        $propietario = $operation->clients->first(); 

        $data = [
            'propietario' => [
                'nombre' => $exclusivity->propietario_nombre ?? ($propietario->name ?? 'Nombre del Propietario'),
                'ci'     => $exclusivity->propietario_ci ?? ($propietario->ci ?? 'V-12.345.678'),
                'rif'    => $exclusivity->propietario_rif ?? '123564',
                'email'  => $exclusivity->propietario_email ?? ($propietario->email ?? ''),
                'phone'  => $exclusivity->propietario_telefono ?? ($propietario->phone ?? ''),
            ],
            'exclusivity' => $exclusivity,
            'fecha_contrato' => $exclusivity && $exclusivity->fecha_firma
                ? \Carbon\Carbon::parse($exclusivity->fecha_firma)->locale('es')->isoFormat('DD [de] MMMM [de] YYYY')
                : now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY'),
            'property' => [
                'price'               => $property ? $property->price : 0,
                'square_meters'       => $property ? $property->square_meters : 0,
                'address'             => $property ? $property->address : '',
                'inmueble_descripcion'=> $exclusivity ? ($exclusivity->inmueble_descripcion ?? '') : '',
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
        /** @var \App\Models\Core\Auth\User|null $authUser */
        $authUser = Auth::user();

        if (!$authUser || !$authUser->isAdmin()) {
            return response()->json(['message' => 'No tienes permiso para editar cierres.'], 403);
        }

        $operation = Operation::where('id', $id)->firstOrFail();

        // Block editing if the property is already Reservado or Vendido
        $property = Property::find($operation->property_id);
        if ($property && in_array($property->status, ['Reservado', 'Vendido'])) {
            return response()->json(['message' => 'Este cierre no puede editarse porque el inmueble ya está ' . $property->status . '.'], 403);
        }

        $amount = $request->amount ?? 0;
        $sellers = ($request->has('sellers') && is_array($request->sellers)) ? $request->sellers : [];
        $numSellers = count($sellers);
        $companyPct = 2.5;
        $companyAmt = round($amount * $companyPct / 100, 2);
        $eachAdvisorPct = $numSellers > 0 ? round(2.5 / $numSellers, 4) : 0;

        // Use per-seller percentages if provided
        $sellersWithPct = ($request->has('sellers_commissions') && is_array($request->sellers_commissions))
            ? $request->sellers_commissions
            : [];

        $operation->update(array_merge(
            $request->only(['type', 'property_id', 'amount', 'property_price', 'start_date', 'end_date', 'notes']),
            [
                'company_commission_percentage' => $companyPct,
                'company_commission_amount' => $companyAmt,
            ]
        ));

        $operation->clients()->sync($request->input('buyers', []));

        if ($numSellers > 0) {
            $syncData = [];
            foreach ($sellers as $sellerId) {
                $customPct = null;
                foreach ($sellersWithPct as $sc) {
                    if ((string)($sc['id'] ?? '') === (string)$sellerId) {
                        $customPct = $sc['percentage'] ?? null;
                        break;
                    }
                }
                $pct = $customPct !== null ? (float)$customPct : $eachAdvisorPct;
                $amt = round($amount * $pct / 100, 2);
                $syncData[$sellerId] = [
                    'commission_percentage' => $pct,
                    'commission_amount' => $amt,
                ];
            }
            $operation->sellers()->sync($syncData);
        } else {
            $operation->sellers()->sync([]);
        }

        return created_responses('Transaction');
    }

    public function show($id)
    {
        $operation = Operation::with(['clients', 'sellers', 'property'])->findOrFail($id);

        $propertyStatus = $operation->property ? $operation->property->status : null;
        $isLocked = in_array($propertyStatus, ['Reservado', 'Vendido']);

        return response()->json([
            'id' => $operation->id,
            'type' => $operation->type,
            'property_id' => (string) $operation->property_id,
            'property_title' => $operation->property ? $operation->property->title : null,
            'property_status' => $propertyStatus,
            'is_locked' => $isLocked,
            'amount' => $operation->amount,
            'property_price' => $operation->property_price,
            'start_date' => $operation->start_date,
            'end_date' => $operation->end_date,
            'notes' => $operation->notes,
            'buyers' => $operation->clients->pluck('id')->map(fn ($item) => (string) $item)->values(),
            'sellers' => $operation->sellers->pluck('id')->map(fn ($item) => (string) $item)->values(),
            'sellers_commissions' => $operation->sellers->map(fn ($s) => [
                'id' => (string) $s->id,
                'percentage' => $s->pivot->commission_percentage ?? 0,
            ])->values(),
        ]);
    }

    public function confirmSale(Request $request, $id)
    {
        /** @var \App\Models\Core\Auth\User|null $authUser */
        $authUser = Auth::user();

        if (!$authUser || !$authUser->isAdmin()) {
            return response()->json(['message' => 'No tienes permiso para confirmar ventas.'], 403);
        }

        $operation = Operation::with(['clients', 'sellers', 'property'])->findOrFail($id);

        if ($operation->type !== 'reserva') {
            return response()->json(['message' => 'Solo se puede confirmar venta de operaciones de tipo reserva.'], 422);
        }

        // Determine the net sale amount:
        //   net_amount = property_price - reservation_amount
        // The caller may override this via request, but we default to the computed value.
        $propertyPrice   = (float) ($operation->property_price ?? 0);
        $reservationAmt  = (float) ($operation->amount ?? 0);
        $defaultNet      = max(0, $propertyPrice - $reservationAmt);
        $netAmount       = $request->has('amount') ? (float) $request->amount : $defaultNet;

        $sellers    = $operation->sellers->pluck('id')->toArray();
        $numSellers = count($sellers);
        $companyPct = 2.5;
        $eachAdvisorPct = $numSellers > 0 ? round(2.5 / $numSellers, 4) : 0;

        // Use per-seller percentages if provided, otherwise recalculate
        $sellersWithPct = ($request->has('sellers_commissions') && is_array($request->sellers_commissions))
            ? $request->sellers_commissions
            : [];

        // Commission on the net sale amount
        $salCompanyAmt = round($netAmount * $companyPct / 100, 2);

        // Preserve the reservation commission earned in the first phase and ADD
        // the new sale commission so total earnings include both phases.
        $reservationCompanyComm = (float) ($operation->company_commission_amount ?? 0);
        $totalCompanyComm       = $reservationCompanyComm + $salCompanyAmt;

        $operation->update([
            'type'                           => 'venta',
            'amount'                         => $netAmount,
            'company_commission_percentage'  => $companyPct,
            'company_commission_amount'      => $totalCompanyComm,
            'reservation_company_commission' => $reservationCompanyComm,
        ]);

        // Update sellers commissions, accumulating reservation + sale
        if ($numSellers > 0) {
            $syncData = [];
            foreach ($sellers as $sellerId) {
                $customPct = null;
                foreach ($sellersWithPct as $sc) {
                    if ((string)($sc['id'] ?? '') === (string)$sellerId) {
                        $customPct = $sc['percentage'] ?? null;
                        break;
                    }
                }
                $pct = $customPct !== null ? (float)$customPct : $eachAdvisorPct;
                $saleAmt = round($netAmount * $pct / 100, 2);

                // Old reservation commission for this seller
                $seller = $operation->sellers->firstWhere('id', $sellerId);
                $reservationSellerAmt = (float) ($seller->pivot->commission_amount ?? 0);

                $syncData[$sellerId] = [
                    'commission_percentage'         => $pct,
                    'commission_amount'             => $reservationSellerAmt + $saleAmt,
                    'reservation_commission_amount' => $reservationSellerAmt,
                ];
            }
            $operation->sellers()->sync($syncData);
        }

        // Update property price if provided and mark as Vendido
        if ($operation->property_id) {
            $updateData = ['status' => 'Vendido'];
            Property::where('id', $operation->property_id)->update($updateData);
        }

        // Create Sale record
        $buyers = $operation->clients->pluck('id')->toArray();
        Sale::create([
            'property_id'  => $operation->property_id,
            'buyer_id'     => $buyers[0] ?? null,
            'seller_id'    => $sellers[0] ?? null,
            'total_amount' => $netAmount,
            'date'         => now(),
            'notes'        => $operation->notes,
        ]);

        return response()->json([
            'message' => 'Venta confirmada correctamente.',
            'data'    => $operation,
            'net_amount' => $netAmount,
            'reservation_amount' => $reservationAmt,
            'property_price' => $propertyPrice,
        ]);
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

        // Only show properties that are not reserved or sold (for new operations)
        $properties = Property::select('id', 'title as value', 'price', 'status')
            ->whereNotIn('status', ['Reservado', 'Vendido'])
            ->get();

        return response()->json([
            'properties' => $properties,
            'clients'    => Client::select('id', 'name as value')->get(),
            'users'      => $users,
        ]);
    }

    public function downloadContract($id)
    {
        $operation = Operation::findOrFail($id);

        if (!$operation->contract_path) {
            return response()->json(['message' => 'No hay contrato disponible para esta operación.'], 404);
        }

        // Use basename() to prevent path traversal
        $fileName = basename($operation->contract_path);
        $filePath = 'public/contracts/' . $fileName;

        if (!Storage::exists($filePath)) {
            return response()->json(['message' => 'El archivo del contrato no fue encontrado.'], 404);
        }

        return response()->download(
            storage_path('app/' . $filePath),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function export(Request $request)
    {
        $fileName = 'cierres_' . now()->format('Y-m-d_H-i') . '.xlsx';
        return Excel::download(new OperationExport($request), $fileName);
    }
}
