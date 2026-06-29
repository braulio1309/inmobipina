<?php

namespace App\Exports;

use App\Models\Operation;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OperationExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Operation::with(['property', 'sellers', 'clients', 'ownerClient', 'buyerClient']);

        // Filter by type
        $types = $this->request->input('type');
        if ($types) {
            $types = is_array($types) ? $types : explode(',', $types);
            $types = array_filter($types);
            if (!empty($types)) {
                $query->whereIn('type', $types);
            }
        }

        // Filter by date range
        $date = $this->request->input('date');
        if (is_string($date)) {
            $date = json_decode(htmlspecialchars_decode($date), true);
        }
        if ($date && is_array($date) && isset($date['start'])) {
            $query->whereBetween(DB::raw('DATE(COALESCE(fecha_cierre, created_at))'), [$date['start'], $date['end']]);
        }

        // Filter by advisor (seller)
        $asesor = $this->request->input('asesor');
        if ($asesor) {
            $query->whereHas('sellers', function ($q) use ($asesor) {
                $q->where('users.id', $asesor);
            });
        }

        // Search filter
        $search = $this->request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('type', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhereHas('property', function ($p) use ($search) {
                      $p->where('title', 'LIKE', "%{$search}%");
                  });
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Vendedor (propietario)',
            'Comprador',
            'Tipo',
            'Propiedad',
            'Medio de captacion',
            'Precio total de la propiedad',
            'Comision total $ (%)',
            'Asesor 1',
            'Asesor 2',
            'Asesor 3',
            'Asesor 4',
            'Asesor 5',
            'Asesor 6',
            'Comision inmobiliaria $',
        ];
    }

    public function map($row): array
    {
        $ownerName = trim((string) optional($row->ownerClient)->name);
        if ($ownerName === '') {
            $ownerName = trim((string) optional($row->clients->first())->name);
        }
        if ($ownerName === '') {
            $ownerName = 'Sin propietario';
        }

        $buyerName = trim((string) optional($row->buyerClient)->name);
        if ($buyerName === '') {
            $buyerName = trim((string) optional($row->clients->skip(1)->first())->name);
        }
        if ($buyerName === '') {
            $buyerName = 'Sin comprador';
        }

        $clientSource = trim((string) (optional($row->buyerClient)->source ?: optional($row->ownerClient)->source ?: ''));
        if ($clientSource === '') {
            $clientSource = 'Sin especificar';
        }

        $propertyTitle = $row->property ? $row->property->title : ($row->external_property_title ?: 'N/A');

        $propertyTotal = (float) ($row->property_price ?? $row->amount ?? 0);
        $propertyTotalFormatted = '$' . number_format($propertyTotal, 2, '.', ',');
        if ($row->type === 'reserva') {
            $reservationAmount = (float) ($row->amount ?? 0);
            $propertyTotalFormatted .= ' | Reserva: $' . number_format($reservationAmount, 2, '.', ',');
        }

        $sellerColumns = $row->sellers
            ->map(function ($seller) {
                $name = trim((string) (($seller->first_name ?? '') . ' ' . ($seller->last_name ?? '')));
                if ($name === '') {
                    $name = 'Asesor';
                }

                $amount = (float) ($seller->pivot->commission_amount ?? 0);
                $percentage = (float) ($seller->pivot->commission_percentage ?? 0);

                return $name . ': $' . number_format($amount, 2, '.', ',') . ' (' . number_format($percentage, 2, '.', ',') . '%)';
            })
            ->values()
            ->all();
        $sellerColumns = array_pad(array_slice($sellerColumns, 0, 6), 6, '');

        $sellerCommissionAmount = (float) $row->sellers->sum(fn ($seller) => $seller->pivot->commission_amount ?? 0);
        $companyCommissionAmount = (float) ($row->company_commission_amount ?? 0);
        $totalCommissionAmount = (float) ($row->total_commission_amount ?? 0);
        if ($totalCommissionAmount <= 0) {
            $totalCommissionAmount = $companyCommissionAmount + $sellerCommissionAmount;
        }

        $commissionBase = (float) ($row->amount ?? 0);
        $totalCommissionPercentage = (float) ($row->total_commission_percentage ?? 0);
        if ($totalCommissionPercentage <= 0 && $commissionBase > 0) {
            $totalCommissionPercentage = ($totalCommissionAmount / $commissionBase) * 100;
        }

        $closingDate = $row->fecha_cierre
            ?: ($row->end_date ?: ($row->start_date ?: optional($row->created_at)->toDateString()));

        return [
            $row->id,
            $closingDate ?: 'N/A',
            $ownerName,
            $buyerName,
            $row->type,
            $propertyTitle,
            $clientSource,
            $propertyTotalFormatted,
            '$' . number_format($totalCommissionAmount, 2, '.', ',') . ' (' . number_format($totalCommissionPercentage, 2, '.', ',') . '%)',
            ...$sellerColumns,
            '$' . number_format($companyCommissionAmount, 2, '.', ','),
        ];
    }
}
