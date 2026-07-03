<?php

namespace App\Exports;

use App\Models\Property;
use App\Models\Core\Auth\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PropertyExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Property::query()
            ->with(['creator', 'agent', 'captation'])
            ->leftJoin('property_captations', 'property_captations.property_id', '=', 'properties.id')
            ->select('properties.*');

        // Filter by type
        $types = $this->request->input('type');
        if ($types) {
            $types = is_array($types) ? $types : explode(',', $types);
            $types = array_filter($types);
            if (!empty($types)) {
                $query->whereIn('type', $types);
            }
        }

        // Filter by type_sale
        $typeSale = $this->request->input('type_sale');
        if ($typeSale) {
            $typeSale = is_array($typeSale) ? $typeSale : explode(',', $typeSale);
            $typeSale = array_filter($typeSale);
            if (!empty($typeSale)) {
                $query->whereIn('type_sale', $typeSale);
            }
        }

        // Filter by status
        $status = $this->request->input('status');
        if ($status) {
            $status = is_array($status) ? $status : explode(',', $status);
            $status = array_filter($status);
            if (!empty($status)) {
                $query->whereIn('status', $status);
            }
        }

        // Filter by date range
        $date = $this->request->input('date');
        if (is_string($date)) {
            $date = json_decode(htmlspecialchars_decode($date), true);
        }
        if ($date && is_array($date) && isset($date['start'])) {
            $query->whereBetween(
                DB::raw('DATE(property_captations.fecha_captacion)'),
                [$date['start'], $date['end']]
            );
        }

        // Filter by price range
        $price = $this->request->input('price');
        if ($price && is_array($price)) {
            if (isset($price['min']) && $price['min'] !== '') {
                $query->where('price', '>=', (float) $price['min']);
            }
            if (isset($price['max']) && $price['max'] !== '') {
                $query->where('price', '<=', (float) $price['max']);
            }
        }

        // Filter by advisor (creator)
        $asesor = $this->request->input('asesor');
        if ($asesor) {
            $query->where('created_by', $asesor);
        }

        // Search filter
        $search = $this->request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhere('type', 'LIKE', "%{$search}%")
                  ->orWhere('type_sale', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%");
            });
        }

        return $query->latest('properties.created_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Título',
            'Dirección',
            'Tipo de Inmueble',
            'Tipo de Oferta',
            'Precio (USD)',
            'Metros²',
            'Habitaciones',
            'Baños',
            'Estatus',
            'Asesor',
            'Fecha de Captación',
        ];
    }

    public function map($row): array
    {
        $creatorName = $row->agent
            ? trim(($row->agent->first_name ?? '') . ' ' . ($row->agent->last_name ?? ''))
            : ($row->creator
                ? trim(($row->creator->first_name ?? '') . ' ' . ($row->creator->last_name ?? ''))
                : 'N/A');

        $captationDate = $row->captation && $row->captation->fecha_captacion
            ? $row->captation->fecha_captacion->format('Y-m-d')
            : ($row->fecha_captacion
                ? $row->fecha_captacion->format('Y-m-d')
                : ($row->created_at ? $row->created_at->format('Y-m-d H:i') : ''));

        return [
            $row->id,
            $row->title,
            $row->address,
            $row->type,
            $row->type_sale,
            $row->price,
            $row->square_meters,
            $row->bedrooms,
            $row->bathrooms,
            $row->status,
            $creatorName,
            $captationDate,
        ];
    }
}
