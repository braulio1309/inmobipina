<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Client::with(['advisor']);

        // Filter by client date range
        $date = $this->request->input('date');
        if (is_string($date)) {
            $date = json_decode(htmlspecialchars_decode($date), true);
        }
        if ($date && is_array($date) && isset($date['start'])) {
            $query->whereBetween(DB::raw('DATE(date)'), [$date['start'], $date['end']]);
        }

        // Filter by advisor (assigned_to)
        $asesor = $this->request->input('asesor');
        if ($asesor) {
            $query->where('assigned_to', $asesor);
        }

        // Filter by status
        $status = $this->request->input('status');
        if ($status) {
            $query->where('status', $status);
        }

        // Search filter
        $search = $this->request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        // Non-admin: only own clients
        /** @var \App\Models\Core\Auth\User $user */
        $user = auth()->user();
        if (!$user->isAdmin()) {
            $query->where(function ($builder) use ($user) {
                $builder->where('user_id', $user->id)
                    ->orWhere('assigned_to', $user->id);
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Email',
            'Teléfono',
            'Medio de captación',
            'Estatus',
            'Asesor asignado',
            'Notas',
            'Fecha',
        ];
    }

    public function map($row): array
    {
        $sourceMap = [
            'telefono'    => 'Teléfono',
            'instagram'   => 'Instagram',
            'tu_inmueble' => 'Tu Inmueble',
            'pendon'      => 'Pendón',
        ];

        $advisorName = $row->advisor
            ? trim(($row->advisor->first_name ?? '') . ' ' . ($row->advisor->last_name ?? ''))
            : 'Sin asignar';

        return [
            $row->id,
            $row->name ?: 'Sin nombre',
            $row->email ?: '',
            $row->phone ?: '',
            $sourceMap[$row->source] ?? ($row->source ?: '—'),
            $row->status ? ucfirst($row->status) : '—',
            $advisorName,
            $row->notes ?: '',
            $row->date ? $row->date->format('Y-m-d') : '',
        ];
    }
}
