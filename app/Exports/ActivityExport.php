<?php

namespace App\Exports;

use App\Models\Activity;
use App\Filters\Core\ActivityFilter;
use App\Filters\Common\Auth\ActivityFilter as AppActivityFilter;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ActivityExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Activity::with('user')
            ->select('activities.*');

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
            $query->whereBetween(DB::raw('DATE(date)'), [$date['start'], $date['end']]);
        }

        // Filter by advisor
        $asesor = $this->request->input('asesor');
        if ($asesor) {
            $query->where('user_id', $asesor);
        }

        // Search filter
        $search = $this->request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('type', 'LIKE', "%{$search}%")
                  ->orWhere('result', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Non-admin: only own activities
        /** @var \App\Models\Core\Auth\User $user */
        $user = auth()->user();
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->latest('date');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Asesor',
            'Tipo',
            'Descripción',
            'Resultado',
            'Fecha',
            'Creado el',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->user ? trim($row->user->first_name . ' ' . ($row->user->last_name ?? '')) : 'N/A',
            $row->type,
            $row->description,
            $row->result,
            $row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d H:i') : '',
            $row->created_at ? $row->created_at->format('Y-m-d H:i') : '',
        ];
    }
}
