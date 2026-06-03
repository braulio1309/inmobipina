<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operation;
use App\Filters\Common\Auth\OperationFilter as AppUserFilter;
use App\Filters\Core\OperationFilter;
use App\Models\Client;
use App\Models\Core\Auth\User;
use App\Models\Property;
use App\Models\Activity;
use App\Services\Core\Auth\OperationService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;
use App\Exports\OperationExport;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;

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
            $this->service->with(['sellers', 'property', 'ownerClient', 'buyerClient'])
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

            $item->property_title = $item->property
                ? $item->property->title
                : ($item->external_property_title ?: '');
            $item->contract_url = $item->contract_path
                ? Storage::url('contracts/' . $item->contract_path)
                : null;
            $item->can_download_commission_pdf = in_array($item->type, ['reserva', 'venta', 'alquiler', 'traspaso']);

            return $item;
        });

        return $operations;
    }

    public function activeRentals()
    {
        $operations = Operation::with([
                'property:id,title,address,status,agent_id,created_by',
                'property.agent:id,first_name,last_name',
                'property.creator:id,first_name,last_name',
                'clients',
                'sellers:id,first_name,last_name,email',
                'ownerClient',
                'buyerClient',
            ])
            ->where('type', 'alquiler')
            ->whereHas('property', function ($query) {
                $query->where('status', 'Alquilado');
            })
            ->orderByDesc('fecha_cierre')
            ->paginate(request()->get('per_page', 10));

        $operations->getCollection()->transform(function (Operation $operation) {
            $resolvedOwnerClient = $this->resolveOperationOwnerClient($operation) ?: $this->makeAnonymousClient();
            $resolvedBuyerClient = $this->resolveOperationBuyerClient($operation, $resolvedOwnerClient) ?: $this->makeAnonymousClient();
            $property = $operation->property;
            $operationAdvisorNames = $operation->sellers
                ->map(fn ($seller) => trim(($seller->first_name ?? '') . ' ' . ($seller->last_name ?? '')) ?: ($seller->email ?? ''))
                ->filter()
                ->values();
            $propertyAdvisor = $property?->agent ?: $property?->creator;
            $propertyAdvisorName = $propertyAdvisor
                ? (trim(($propertyAdvisor->first_name ?? '') . ' ' . ($propertyAdvisor->last_name ?? '')) ?: 'Sin asesor')
                : 'Sin asesor';

            return [
                'id' => $operation->id,
                'property_title' => $property?->title ?: ($operation->external_property_title ?: 'Sin título'),
                'property_address' => $property?->address ?: 'Sin ubicación',
                'operation_amount' => $operation->amount,
                'owner_name' => $resolvedOwnerClient->name ?: 'Sin propietario',
                'buyer_name' => $resolvedBuyerClient->name ?: 'Sin comprador',
                'operation_advisor_name' => $operationAdvisorNames->isNotEmpty()
                    ? $operationAdvisorNames->implode(', ')
                    : $propertyAdvisorName,
                'payment_frequency' => $this->formatPaymentFrequencyLabel($operation->payment_frequency),
                'next_cutoff_date' => $this->calculateNextRentalCutoffDate($operation),
                'final_date' => $operation->fecha_corte,
            ];
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

    $amount = $request->amount ?? 0;
    $sellers = ($request->has('sellers') && is_array($request->sellers)) ? $request->sellers : [];
    $numSellers = count($sellers);
    $participants = $this->resolveOperationParticipants($request);
    $isRental = $request->input('type') === 'alquiler';
    $mesesAdelanto = max(0, (int) $request->input('meses_adelanto', 0));
    $mesAdministrativo = max(0, (int) $request->input('mes_administrativo', 0));
    $fechaInicio = $request->input('start_date');
    $fechaCorte = $request->input('fecha_corte');
    $paymentFrequency = $request->input('payment_frequency');

    if ($isRental && $numSellers === 0) {
        return response()->json(['message' => 'Debes seleccionar al menos un asesor para el alquiler.'], 422);
    }

    if ($isRental && blank($fechaInicio)) {
        return response()->json(['message' => 'Debes indicar la fecha inicio del alquiler.'], 422);
    }

    if ($isRental && blank($fechaCorte)) {
        return response()->json(['message' => 'Debes indicar la fecha corte del alquiler.'], 422);
    }

    if ($isRental && blank($paymentFrequency)) {
        return response()->json(['message' => 'Debes indicar el tiempo de pago del alquiler.'], 422);
    }

    if ($isRental && strtotime((string) $fechaCorte) < strtotime((string) $fechaInicio)) {
        return response()->json(['message' => 'La fecha corte del alquiler no puede ser menor que la fecha inicio.'], 422);
    }

    $defaultTotalCommissionAmount = $isRental
        ? round(((float) $amount) * $mesAdministrativo, 2)
        : round(((float) $amount) * 0.05, 2);
    $requestedTotalCommissionAmount = $request->filled('total_commission_amount')
        ? max(0, (float) $request->input('total_commission_amount'))
        : $defaultTotalCommissionAmount;
    $totalCommissionAmt = $requestedTotalCommissionAmount;
    $totalCommissionPct = (float) $amount > 0
        ? round(($totalCommissionAmt / (float) $amount) * 100, 4)
        : 0.0;
    $defaultCompanyPct = $isRental
        ? round($totalCommissionPct / 2, 4)
        : ($numSellers > 0 ? min(2.5, $totalCommissionPct) : $totalCommissionPct);
    $requestedCompanyAmount = $request->filled('company_commission_amount')
        ? max(0, (float) $request->input('company_commission_amount'))
        : ($isRental ? round($totalCommissionAmt / 2, 2) : round(((float) $amount) * $defaultCompanyPct / 100, 2));
    $companyAmt = $requestedCompanyAmount;
    $companyAmt = min($companyAmt, $totalCommissionAmt);
    $companyPct = (float) $amount > 0
        ? round(($companyAmt / (float) $amount) * 100, 4)
        : 0.0;
    $remainingAmount = max(0, $totalCommissionAmt - $companyAmt);
    $eachAdvisorAmt = $numSellers > 0 ? round($remainingAmount / $numSellers, 2) : 0;

    // Use per-seller percentages if provided
    $sellersWithPct = ($request->has('sellers_commissions') && is_array($request->sellers_commissions))
        ? $request->sellers_commissions
        : [];
    $requestedSellerTotal = collect($sellersWithPct)
        ->filter(fn ($item) => in_array((string) ($item['id'] ?? ''), array_map('strval', $sellers), true))
        ->sum(fn ($item) => max(0, (float) ($item['amount'] ?? 0)));

    if ($requestedSellerTotal > ($remainingAmount + 0.0001)) {
        return response()->json(['message' => 'La suma de comisiones de asesores supera el monto total disponible.'], 422);
    }

    // 1. Crear la Operación
    $operation = Operation::create(array_merge(
        $request->only(['type', 'property_id', 'amount', 'property_price', 'start_date', 'end_date', 'fecha_cierre', 'meses_adelanto', 'mes_administrativo', 'fecha_corte', 'payment_frequency', 'notes', 'external_property_title']),
        $participants,
        [
            'meses_adelanto' => $isRental ? $mesesAdelanto : 0,
            'mes_administrativo' => $isRental ? $mesAdministrativo : 0,
            'fecha_corte' => $isRental ? $fechaCorte : null,
            'payment_frequency' => $isRental ? $paymentFrequency : null,
            'total_commission_percentage' => $totalCommissionPct,
            'total_commission_amount' => $totalCommissionAmt,
            'company_commission_percentage' => $companyPct,
            'company_commission_amount'     => $companyAmt,
        ]
    ));

    $operation->clients()->sync($this->buildClientSyncIdsFromParticipants($participants));

    // Guardar sellers con comisiones
    if ($numSellers > 0) {
        $syncData = [];
        foreach ($sellers as $sellerId) {
            $customPct = null;
            $customAmt = null;
            foreach ($sellersWithPct as $sc) {
                if ((string)($sc['id'] ?? '') === (string)$sellerId) {
                    $customPct = $sc['percentage'] ?? null;
                    $customAmt = $sc['amount'] ?? null;
                    break;
                }
            }
            $amt = $customAmt !== null ? max(0, (float) $customAmt) : $eachAdvisorAmt;
            $pct = (float) $amount > 0 ? round(($amt / (float) $amount) * 100, 4) : 0.0;
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
    } elseif ($operation->type === 'alquiler') {
        Property::where('id', $operation->property_id)->update(['status' => 'Alquilado']);
    } elseif (in_array($operation->type, ['venta', 'traspaso'])) {
        Property::where('id', $operation->property_id)->update(['status' => 'Vendido']);
    }

    // *************************************************************************
    // LOGICA PARA VENTA Y TRASPASO (Crear registro en tabla Sales)
    // *************************************************************************
    if ($operation->type == 'venta' || $operation->type == 'traspaso') {
        Sale::create([
            'property_id'  => $operation->property_id,
            'buyer_id'     => $request->buyer_client_id,
            'seller_id'    => $request->sellers[0] ?? null,
            'total_amount' => $operation->amount,
            'date'         => now(),
            'notes'        => $request->notes
        ]);
    }

    $this->registerClosingActivities($operation->fresh(['property', 'sellers', 'ownerClient', 'buyerClient']));

    // *************************************************************************
    // LOGICA PARA EXCLUSIVIDAD (Generar PDF)
    // *************************************************************************
    $pdfUrl = null;

    if ($operation->type == 'exclusividad') {
        $property = $operation->property;
        $exclusivity = $property ? $property->exclusivities()->latest()->first() : null;
        $propietario = $operation->ownerClient;

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

        // Block editing if the property is already Reservado, Vendido or Alquilado
        $property = Property::find($operation->property_id);
        if ($property && in_array($property->status, ['Reservado', 'Vendido', 'Alquilado'])) {
            return response()->json(['message' => 'Este cierre no puede editarse porque el inmueble ya está ' . $property->status . '.'], 403);
        }

        $amount = $request->amount ?? 0;
        $sellers = ($request->has('sellers') && is_array($request->sellers)) ? $request->sellers : [];
        $numSellers = count($sellers);
        $participants = $this->resolveOperationParticipants($request);
        $isRental = $request->input('type') === 'alquiler';
        $mesesAdelanto = max(0, (int) $request->input('meses_adelanto', 0));
        $mesAdministrativo = max(0, (int) $request->input('mes_administrativo', 0));
        $fechaInicio = $request->input('start_date');
        $fechaCorte = $request->input('fecha_corte');
        $paymentFrequency = $request->input('payment_frequency');

        if ($isRental && $numSellers === 0) {
            return response()->json(['message' => 'Debes seleccionar al menos un asesor para el alquiler.'], 422);
        }

        if ($isRental && blank($fechaInicio)) {
            return response()->json(['message' => 'Debes indicar la fecha inicio del alquiler.'], 422);
        }

        if ($isRental && blank($fechaCorte)) {
            return response()->json(['message' => 'Debes indicar la fecha corte del alquiler.'], 422);
        }

        if ($isRental && blank($paymentFrequency)) {
            return response()->json(['message' => 'Debes indicar el tiempo de pago del alquiler.'], 422);
        }

        if ($isRental && strtotime((string) $fechaCorte) < strtotime((string) $fechaInicio)) {
            return response()->json(['message' => 'La fecha corte del alquiler no puede ser menor que la fecha inicio.'], 422);
        }

        $defaultTotalCommissionAmount = $isRental
            ? round(((float) $amount) * $mesAdministrativo, 2)
            : round(((float) $amount) * 0.05, 2);
        $requestedTotalCommissionAmount = $request->filled('total_commission_amount')
            ? max(0, (float) $request->input('total_commission_amount'))
            : (float) ($operation->total_commission_amount ?? $defaultTotalCommissionAmount);
        $totalCommissionAmt = $requestedTotalCommissionAmount;
        $totalCommissionPct = (float) $amount > 0
            ? round(($totalCommissionAmt / (float) $amount) * 100, 4)
            : 0.0;
        $defaultCompanyPct = $isRental
            ? round($totalCommissionPct / 2, 4)
            : ($numSellers > 0 ? min(2.5, $totalCommissionPct) : $totalCommissionPct);
        $requestedCompanyAmount = $request->filled('company_commission_amount')
            ? max(0, (float) $request->input('company_commission_amount'))
            : (float) ($operation->company_commission_amount ?? ($isRental ? round($totalCommissionAmt / 2, 2) : round(((float) $amount) * $defaultCompanyPct / 100, 2)));
        $companyAmt = $requestedCompanyAmount;
        $companyAmt = min($companyAmt, $totalCommissionAmt);
        $companyPct = (float) $amount > 0
            ? round(($companyAmt / (float) $amount) * 100, 4)
            : 0.0;
        $remainingAmount = max(0, $totalCommissionAmt - $companyAmt);
        $eachAdvisorAmt = $numSellers > 0 ? round($remainingAmount / $numSellers, 2) : 0;

        // Use per-seller percentages if provided
        $sellersWithPct = ($request->has('sellers_commissions') && is_array($request->sellers_commissions))
            ? $request->sellers_commissions
            : [];
        $requestedSellerTotal = collect($sellersWithPct)
            ->filter(fn ($item) => in_array((string) ($item['id'] ?? ''), array_map('strval', $sellers), true))
            ->sum(fn ($item) => max(0, (float) ($item['amount'] ?? 0)));

        if ($requestedSellerTotal > ($remainingAmount + 0.0001)) {
            return response()->json(['message' => 'La suma de comisiones de asesores supera el monto total disponible.'], 422);
        }

        $operation->update(array_merge(
            $request->only(['type', 'property_id', 'amount', 'property_price', 'start_date', 'end_date', 'fecha_cierre', 'meses_adelanto', 'mes_administrativo', 'fecha_corte', 'payment_frequency', 'notes', 'external_property_title']),
            $participants,
            [
                'meses_adelanto' => $isRental ? $mesesAdelanto : 0,
                'mes_administrativo' => $isRental ? $mesAdministrativo : 0,
                'fecha_corte' => $isRental ? $fechaCorte : null,
                'payment_frequency' => $isRental ? $paymentFrequency : null,
                'total_commission_percentage' => $totalCommissionPct,
                'total_commission_amount' => $totalCommissionAmt,
                'company_commission_percentage' => $companyPct,
                'company_commission_amount' => $companyAmt,
            ]
        ));

        $operation->clients()->sync($this->buildClientSyncIdsFromParticipants($participants));

        if ($numSellers > 0) {
            $syncData = [];
            foreach ($sellers as $sellerId) {
                $customPct = null;
                $customAmt = null;
                foreach ($sellersWithPct as $sc) {
                    if ((string)($sc['id'] ?? '') === (string)$sellerId) {
                        $customPct = $sc['percentage'] ?? null;
                        $customAmt = $sc['amount'] ?? null;
                        break;
                    }
                }
                $amt = $customAmt !== null ? max(0, (float) $customAmt) : $eachAdvisorAmt;
                $pct = (float) $amount > 0 ? round(($amt / (float) $amount) * 100, 4) : 0.0;
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
        $operation = Operation::with(['clients', 'sellers', 'property', 'ownerClient', 'buyerClient'])->findOrFail($id);
        $resolvedOwnerClient = $this->resolveOperationOwnerClient($operation);
        $resolvedBuyerClient = $this->resolveOperationBuyerClient($operation, $resolvedOwnerClient);
        $sellerCommissionAmount = (float) $operation->sellers->sum(fn ($seller) => $seller->pivot->commission_amount ?? 0);
        $derivedTotalCommissionAmount = (float) ($operation->company_commission_amount ?? 0) + $sellerCommissionAmount;
        $rentalReferenceCommissionAmount = $operation->type === 'alquiler'
            ? round(((float) ($operation->amount ?? 0)) * ((int) ($operation->mes_administrativo ?? 0)), 2)
            : 0;

        $propertyStatus = $operation->property ? $operation->property->status : null;
        $isLocked = in_array($propertyStatus, ['Reservado', 'Vendido', 'Alquilado']);

        return response()->json([
            'id' => $operation->id,
            'type' => $operation->type,
            'property_id' => (string) $operation->property_id,
            'property_title' => $operation->property ? $operation->property->title : null,
            'external_property_title' => $operation->external_property_title,
            'property_status' => $propertyStatus,
            'is_locked' => $isLocked,
            'amount' => $operation->amount,
            'property_price' => $operation->property_price,
            'start_date' => $operation->start_date,
            'end_date' => $operation->end_date,
            'fecha_cierre' => $operation->fecha_cierre,
            'meses_adelanto' => $operation->meses_adelanto,
            'mes_administrativo' => $operation->mes_administrativo,
            'fecha_corte' => $operation->fecha_corte,
            'payment_frequency' => $operation->payment_frequency,
            'notes' => $operation->notes,
            'total_commission_percentage' => $operation->total_commission_percentage,
            'total_commission_amount' => (float) ($operation->total_commission_amount ?? 0) > 0
                ? $operation->total_commission_amount
                : ($operation->type === 'alquiler' && $rentalReferenceCommissionAmount > 0
                    ? $rentalReferenceCommissionAmount
                    : $derivedTotalCommissionAmount),
            'company_commission_percentage' => $operation->company_commission_percentage,
            'company_commission_amount' => $operation->company_commission_amount,
            'owner_client_id' => $resolvedOwnerClient?->id ? (string) $resolvedOwnerClient->id : '',
            'buyer_client_id' => $resolvedBuyerClient?->id ? (string) $resolvedBuyerClient->id : '',
            'owner_client_name' => $resolvedOwnerClient?->name,
            'buyer_client_name' => $resolvedBuyerClient?->name,
            'sellers' => $operation->sellers->pluck('id')->map(fn ($item) => (string) $item)->values(),
            'sellers_commissions' => $operation->sellers->map(fn ($s) => [
                'id' => (string) $s->id,
                'percentage' => $s->pivot->commission_percentage ?? 0,
                'amount' => $s->pivot->commission_amount ?? 0,
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

        $operation = Operation::with(['clients', 'sellers', 'property', 'buyerClient'])->findOrFail($id);

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
        $totalCommissionPct = $request->filled('total_commission_percentage')
            ? max(0, (float) $request->input('total_commission_percentage'))
            : (float) ($operation->total_commission_percentage ?? 5);
        $defaultCompanyPct = $numSellers > 0 ? min((float) ($operation->company_commission_percentage ?? 2.5), $totalCommissionPct) : $totalCommissionPct;
        $companyPct = $request->filled('company_commission_percentage')
            ? (float) $request->input('company_commission_percentage')
            : $defaultCompanyPct;
        $companyPct = max(0, min($companyPct, $totalCommissionPct));
        $remainingPct = max(0, $totalCommissionPct - $companyPct);
        $eachAdvisorPct = $numSellers > 0 ? round($remainingPct / $numSellers, 4) : 0;

        // Use per-seller percentages if provided, otherwise recalculate
        $sellersWithPct = ($request->has('sellers_commissions') && is_array($request->sellers_commissions))
            ? $request->sellers_commissions
            : [];
        $requestedSellerTotal = collect($sellersWithPct)
            ->filter(fn ($item) => in_array((string) ($item['id'] ?? ''), array_map('strval', $sellers), true))
            ->sum(fn ($item) => (float) ($item['percentage'] ?? 0));

        if ($requestedSellerTotal > ($remainingPct + 0.0001)) {
            return response()->json(['message' => 'La suma de comisiones de asesores supera el porcentaje total disponible.'], 422);
        }

        // Commission on the net sale amount
        $salCompanyAmt = round($netAmount * $companyPct / 100, 2);

        // Preserve the reservation commission earned in the first phase and ADD
        // the new sale commission so total earnings include both phases.
        $reservationCompanyComm = (float) ($operation->company_commission_amount ?? 0);
        $totalCompanyComm       = $reservationCompanyComm + $salCompanyAmt;

        $operation->update([
            'type'                           => 'venta',
            'amount'                         => $netAmount,
            'total_commission_percentage'    => $totalCommissionPct,
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
        Sale::create([
            'property_id'  => $operation->property_id,
            'buyer_id'     => $operation->buyer_client_id,
            'seller_id'    => $sellers[0] ?? null,
            'total_amount' => $netAmount,
            'date'         => now(),
            'notes'        => $operation->notes,
        ]);

        $this->registerClosingActivities($operation->fresh(['property', 'sellers', 'ownerClient', 'buyerClient']));

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
        $hasClientCi = true;

        try {
            $clients = Client::select('id', 'name', 'email', 'phone', 'ci')->get();
        } catch (\Throwable $exception) {
            report($exception);
            $hasClientCi = false;
            $clients = Client::select('id', 'name', 'email', 'phone')->get();
        }

        try {
            $users = User::select('id', 'first_name', 'last_name', 'email')
                ->orderBy('first_name')
                ->get()
                ->map(function ($user) {
                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

                    return [
                        'id' => $user->id,
                        'value' => $fullName !== '' ? $fullName : ($user->email ?? 'Usuario #' . $user->id),
                    ];
                })
                ->values();
        } catch (\Throwable $exception) {
            report($exception);
            $users = collect();
        }

        try {
            // Include properties with null/empty status and exclude only locked ones.
            $properties = Property::with([
                    'captation',
                    'exclusivities' => function ($query) {
                        $query->latest();
                    },
                ])
                ->select('id', 'title', 'price', 'status')
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '')
                        ->orWhereNotIn('status', ['Reservado', 'Vendido', 'Alquilado']);
                })
                ->get()
                ->map(function ($property) use ($clients) {
                    try {
                        $suggestedOwnerClient = $this->resolveSuggestedOwnerClient($property, $clients);
                    } catch (\Throwable $exception) {
                        report($exception);
                        $suggestedOwnerClient = null;
                    }

                    return [
                        'id' => $property->id,
                        'value' => $property->title,
                        'price' => $property->price,
                        'status' => $property->status,
                        'suggested_owner_client_id' => $suggestedOwnerClient?->id,
                        'suggested_owner_client_name' => $suggestedOwnerClient?->name,
                    ];
                })
                ->values();
        } catch (\Throwable $exception) {
            report($exception);
            $properties = collect();
        }

        return response()->json([
            'properties' => $properties,
            'clients'    => $clients->map(fn ($client) => [
                'id' => $client->id,
                'value' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'ci' => $hasClientCi ? $client->ci : null,
            ])->values(),
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

    public function downloadCommissionReceipt($id)
    {
        $operation = Operation::with([
            'clients',
            'sellers',
            'ownerClient',
            'buyerClient',
            'property.captation',
            'property.exclusivities' => function ($query) {
                $query->latest();
            },
        ])->findOrFail($id);

        if (!in_array($operation->type, ['reserva', 'venta', 'alquiler', 'traspaso'])) {
            return response()->json(['message' => 'Esta operación no tiene recibo de pago de comisión.'], 422);
        }

        $resolvedOwnerClient = $this->resolveOperationOwnerClient($operation) ?: $this->makeAnonymousClient();
        $resolvedBuyerClient = $this->resolveOperationBuyerClient($operation, $resolvedOwnerClient) ?: $this->makeAnonymousClient();

        $pdf = Pdf::loadView('pdf.pago-comision', [
            'operation' => $operation,
            'advisors' => $operation->sellers,
            'property' => $operation->property,
            'ownerClient' => $resolvedOwnerClient,
            'buyerClient' => $resolvedBuyerClient,
            'companyRepresentative' => [
                'name' => 'Luis Rafael Pinango',
                'ci' => '5.907.985',
                'rif' => 'J-29788405-0',
            ],
        ]);

        $fileName = 'pago_comision_operacion_' . $operation->id . '.pdf';

        return $pdf->download($fileName);
    }

    public function export(Request $request)
    {
        $fileName = 'cierres_' . now()->format('Y-m-d_H-i') . '.xlsx';
        return Excel::download(new OperationExport($request), $fileName);
    }

    private function buildClientSyncIdsFromParticipants(array $participants): array
    {
        return collect([
            $participants['owner_client_id'] ?? null,
            $participants['buyer_client_id'] ?? null,
        ])->filter()->unique()->values()->all();
    }

    private function resolveOperationParticipants(Request $request): array
    {
        return [
            'owner_client_id' => $this->resolveClientId(
                $request->input('owner_client_id'),
                $request->input('owner_client_name_manual')
            ),
            'buyer_client_id' => $this->resolveClientId(
                $request->input('buyer_client_id'),
                $request->input('buyer_client_name_manual')
            ),
        ];
    }

    private function resolveClientId($clientId, $manualName = null): ?int
    {
        if (filled($clientId) && is_numeric($clientId)) {
            return (int) $clientId;
        }

        $candidateName = trim((string) $manualName);

        if ($candidateName === '' && filled($clientId) && !is_numeric($clientId)) {
            $candidateName = trim((string) $clientId);
        }

        if ($candidateName === '') {
            return null;
        }

        $client = Client::query()->firstOrCreate([
            'name' => $candidateName,
        ]);

        return $client->id;
    }

    private function resolveOperationOwnerClient(Operation $operation)
    {
        $ownerFallback = $this->extractOwnerFallbackData($operation->property);

        if ($operation->ownerClient) {
            return $this->mergeParticipantData($operation->ownerClient, $ownerFallback);
        }

        $suggestedOwner = $this->resolveSuggestedOwnerClient($operation->property, $operation->clients);
        if ($suggestedOwner) {
            return $this->mergeParticipantData($suggestedOwner, $ownerFallback);
        }

        $fallbackClient = $operation->clients->first();
        if ($fallbackClient) {
            return $this->mergeParticipantData($fallbackClient, $ownerFallback);
        }

        return $ownerFallback ?: $this->makeAnonymousClient();
    }

    private function resolveOperationBuyerClient(Operation $operation, $resolvedOwnerClient = null)
    {
        if ($operation->buyerClient) {
            return $this->sanitizeParticipant($operation->buyerClient);
        }

        $fallbackBuyer = $this->resolveBuyerClientFallback($operation, $resolvedOwnerClient);

        return $fallbackBuyer
            ? $this->sanitizeParticipant($fallbackBuyer)
            : $this->makeAnonymousClient();
    }

    private function resolveSuggestedOwnerClient(?Property $property, $clients)
    {
        if (!$property) {
            return null;
        }

        $ownerName = trim((string) optional($property->captation)->cliente_nombre_apellido);
        $ownerCi = trim((string) optional($property->captation)->autorizacion_cedula);
        $ownerEmail = trim((string) optional($property->captation)->cliente_correo_electronico);
        $ownerPhone = trim((string) optional($property->captation)->cliente_nro_contacto);

        if ($ownerName === '' && $property->exclusivities->isNotEmpty()) {
            $latestExclusivity = $property->exclusivities->first();
            $ownerName = trim((string) $latestExclusivity->propietario_nombre);
            $ownerCi = trim((string) $latestExclusivity->propietario_ci);
            $ownerEmail = trim((string) $latestExclusivity->propietario_email);
            $ownerPhone = trim((string) $latestExclusivity->propietario_telefono);
        }

        if ($ownerName === '' && $ownerCi === '' && $ownerEmail === '' && $ownerPhone === '') {
            return null;
        }

        return $clients->first(function ($client) use ($ownerName, $ownerCi, $ownerEmail, $ownerPhone) {
            if ($ownerCi !== '' && trim((string) $client->ci) !== '' && trim((string) $client->ci) === $ownerCi) {
                return true;
            }

            if ($ownerEmail !== '' && trim((string) $client->email) !== '' && strcasecmp(trim((string) $client->email), $ownerEmail) === 0) {
                return true;
            }

            if ($ownerPhone !== '' && trim((string) $client->phone) !== '' && trim((string) $client->phone) === $ownerPhone) {
                return true;
            }

            return $ownerName !== '' && strcasecmp(trim((string) $client->name), $ownerName) === 0;
        });
    }

    private function resolveBuyerClientFallback(Operation $operation, $resolvedOwnerClient)
    {
        return $operation->clients->first(function ($client) use ($resolvedOwnerClient) {
            return !$this->participantMatchesClient($resolvedOwnerClient, $client);
        });
    }

    private function extractOwnerFallbackData(?Property $property)
    {
        if (!$property) {
            return null;
        }

        $captation = $property->captation;
        $latestExclusivity = $property->exclusivities->first();

        $participant = (object) [
            'id' => null,
            'name' => $this->firstFilledValue([
                optional($captation)->autorizacion_nombre,
                optional($captation)->cliente_nombre_apellido,
                optional($latestExclusivity)->propietario_nombre,
            ]),
            'ci' => $this->firstFilledValue([
                optional($captation)->autorizacion_cedula,
                optional($latestExclusivity)->propietario_ci,
            ]),
            'email' => $this->firstFilledValue([
                optional($captation)->cliente_correo_electronico,
                optional($latestExclusivity)->propietario_email,
            ]),
            'phone' => $this->firstFilledValue([
                optional($captation)->cliente_nro_contacto,
                optional($latestExclusivity)->propietario_telefono,
            ]),
        ];

        return $this->hasParticipantData($participant) ? $participant : null;
    }

    private function mergeParticipantData($participant, $fallback = null)
    {
        $base = $this->sanitizeParticipant($participant);

        if (!$fallback) {
            return $base;
        }

        $fallback = $this->sanitizeParticipant($fallback);

        return (object) [
            'id' => $base->id,
            'name' => $base->name !== '' ? $base->name : $fallback->name,
            'ci' => $base->ci !== '' ? $base->ci : $fallback->ci,
            'email' => $base->email !== '' ? $base->email : $fallback->email,
            'phone' => $base->phone !== '' ? $base->phone : $fallback->phone,
        ];
    }

    private function sanitizeParticipant($participant)
    {
        return (object) [
            'id' => $participant->id ?? null,
            'name' => trim((string) ($participant->name ?? '')),
            'ci' => trim((string) ($participant->ci ?? '')),
            'email' => trim((string) ($participant->email ?? '')),
            'phone' => trim((string) ($participant->phone ?? '')),
        ];
    }

    private function participantMatchesClient($participant, $client): bool
    {
        if (!$participant || !$client) {
            return false;
        }

        if (($participant->id ?? null) && ($client->id ?? null) && (string) $participant->id === (string) $client->id) {
            return true;
        }

        foreach (['ci', 'email', 'phone', 'name'] as $field) {
            $participantValue = trim((string) ($participant->{$field} ?? ''));
            $clientValue = trim((string) ($client->{$field} ?? ''));

            if ($participantValue !== '' && $clientValue !== '' && strcasecmp($participantValue, $clientValue) === 0) {
                return true;
            }
        }

        return false;
    }

    private function hasParticipantData($participant): bool
    {
        return collect(['name', 'ci', 'email', 'phone'])
            ->contains(fn ($field) => trim((string) ($participant->{$field} ?? '')) !== '');
    }

    private function firstFilledValue(array $values): string
    {
        foreach ($values as $value) {
            $normalized = trim((string) $value);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    private function formatPaymentFrequencyLabel(?string $paymentFrequency): string
    {
        return match (trim((string) $paymentFrequency)) {
            'quincenal' => 'Quincenal',
            'mensual' => 'Mensual',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            default => 'Sin definir',
        };
    }

    private function calculateNextRentalCutoffDate(Operation $operation): ?string
    {
        $startDate = $operation->start_date ? Carbon::parse($operation->start_date)->startOfDay() : null;
        $finalDate = $operation->fecha_corte ? Carbon::parse($operation->fecha_corte)->startOfDay() : null;
        $paymentFrequency = trim((string) $operation->payment_frequency);

        if (!$startDate || !$finalDate || $paymentFrequency === '') {
            return null;
        }

        $today = Carbon::today();
        $nextDate = $startDate->copy();

        while ($nextDate->lt($today)) {
            $candidate = match ($paymentFrequency) {
                'quincenal' => $nextDate->copy()->addDays(15),
                'mensual' => $nextDate->copy()->addMonthNoOverflow(),
                'semestral' => $nextDate->copy()->addMonthsNoOverflow(6),
                'anual' => $nextDate->copy()->addYearNoOverflow(),
                default => null,
            };

            if (!$candidate || $candidate->equalTo($nextDate)) {
                break;
            }

            $nextDate = $candidate;
        }

        if ($nextDate->gt($finalDate)) {
            return null;
        }

        return $nextDate->toDateString();
    }

    private function registerClosingActivities(Operation $operation): void
    {
        if ($operation->sellers->isEmpty()) {
            return;
        }

        $activityDate = $operation->fecha_cierre
            ? Carbon::parse($operation->fecha_cierre)->startOfDay()
            : ($operation->end_date
                ? Carbon::parse($operation->end_date)->startOfDay()
                : ($operation->start_date
                    ? Carbon::parse($operation->start_date)->startOfDay()
                    : now()->startOfDay()));

        $propertyLabel = trim((string) ($operation->property?->title ?: $operation->external_property_title ?: ($operation->property?->address ?: ('Propiedad #' . $operation->property_id))));
        $counterparty = $operation->buyerClient?->name ?: $operation->ownerClient?->name;
        $typeLabel = trim((string) ($operation->type ?: 'cierre'));
        $description = 'Cierre de ' . $typeLabel . ' registrado';

        if ($propertyLabel !== '') {
            $description .= ' para ' . $propertyLabel;
        }

        if (trim((string) $counterparty) !== '') {
            $description .= ' con ' . trim((string) $counterparty);
        }

        foreach ($operation->sellers as $seller) {
            if (!$seller->id) {
                continue;
            }

            Activity::create([
                'user_id' => $seller->id,
                'client_id' => $operation->buyer_client_id ?: $operation->owner_client_id,
                'property_id' => $operation->property_id,
                'type' => $typeLabel,
                'description' => $description,
                'date' => $activityDate,
            ]);
        }
    }

    private function makeAnonymousClient()
    {
        return (object) [
            'id' => null,
            'name' => '',
            'ci' => '',
            'email' => '',
            'phone' => '',
        ];
    }
}
