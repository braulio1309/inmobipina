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
        $query = Operation::with(['property', 'sellers', 'clients']);

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
            $query->whereBetween(DB::raw('DATE(created_at)'), [$date['start'], $date['end']]);
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
            'Tipo',
            'Propiedad',
            'Monto',
            'Precio Propiedad',
            'Comisión Inmobiliaria',
            'Asesores',
            'Compradores',
            'Notas',
            'Fecha de Creación',
        ];
    }

    public function map($row): array
    {
        $sellers = $row->sellers->map(fn($s) => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')))->filter()->implode(', ');
        $buyers  = $row->clients->map(fn($c) => $c->name)->filter()->implode(', ');

        return [
            $row->id,
            $row->type,
            $row->property ? $row->property->title : 'N/A',
            $row->amount,
            $row->property_price,
            $row->company_commission_amount,
            $sellers ?: 'N/A',
            $buyers  ?: 'N/A',
            $row->notes,
            $row->created_at ? $row->created_at->format('Y-m-d H:i') : '',
        ];
    }
}
