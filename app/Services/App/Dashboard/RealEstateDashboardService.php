<?php

namespace App\Services\App\Dashboard;

use App\Models\Exclusivity;
use App\Models\Operation;
use App\Models\Property;
use App\Models\Activity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealEstateDashboardService
{
    public function getDashboardData($startDate = null, $endDate = null)
    {
        // Set default dates if not provided (last 30 days)
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30)->startOfDay();
        } else {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        if (!$endDate) {
            $endDate = Carbon::now()->endOfDay();
        } else {
            $endDate = Carbon::parse($endDate)->endOfDay();
        }

        return [
            'defaultData' => $this->getDefaultData($startDate, $endDate),
            'activitiesByType' => $this->getActivitiesByType($startDate, $endDate),
            'latestNegotiations' => $this->getLatestNegotiations($startDate, $endDate),
            'topSellers' => $this->getTopSellers($startDate, $endDate),
            'salesOverTime' => $this->getSalesOverTime($startDate, $endDate),
        ];
    }

    private function getDefaultData($startDate, $endDate)
    {
        $totalClosingsRevenue = DB::table('operations')
            ->whereIn('type', $this->closingOperationTypes())
            ->whereBetween(DB::raw($this->operationDateExpression()), [$startDate, $endDate])
            ->sum(DB::raw($this->operationAmountExpression()));

        // Count of exclusivities
        $exclusivitiesCount = Exclusivity::whereBetween('start_date', [$startDate, $endDate])
            ->count();

        // Count of captaciones (properties created and approved)
        $captacionesCount = Property::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('approved_by')
            ->count();

        // Total properties
        $totalProperties = Property::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Total company commission from operations in the date range
        $totalCompanyCommission = DB::table('operations')
            ->whereBetween(DB::raw($this->operationDateExpression()), [$startDate, $endDate])
            ->whereIn('type', $this->closingOperationTypes())
            ->sum('company_commission_amount');

        return [
            ['label' => 'Total Ganancias en Cierres', 'number' => $totalClosingsRevenue, 'icon' => 'dollar-sign'],
            ['label' => 'Exclusividades', 'number' => $exclusivitiesCount, 'icon' => 'award'],
            ['label' => 'Captaciones Aprobadas', 'number' => $captacionesCount, 'icon' => 'home'],
            ['label' => 'Comisión Inmobiliaria', 'number' => $totalCompanyCommission, 'icon' => 'percent'],
        ];
    }

    private function getActivitiesByType($startDate, $endDate)
    {
        $activities = Activity::select('type', DB::raw('COUNT(*) as count'))
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('type')
            ->get();

        $labels = [];
        $data = [];
        $colors = ['#713bdb', '#348cd4', '#f75320', '#4caf50', '#ff9800', '#e91e63', '#00bcd4', '#8bc34a'];
        $backgroundColors = [];

        foreach ($activities as $index => $activity) {
            $labels[] = $activity->type ?: 'Sin Tipo';
            $data[] = $activity->count;
            $backgroundColors[] = $colors[$index % count($colors)];
        }

        $dataSet = [
            [
                'borderWidth' => 0,
                'backgroundColor' => $backgroundColors,
                'data' => $data
            ]
        ];

        $chartElement = [];
        foreach ($activities as $index => $activity) {
            $chartElement[] = [
                'key' => $activity->type ?: 'Sin Tipo',
                'value' => $activity->count,
                'background_color' => 'background-color: ' . $backgroundColors[$index % count($colors)] . ';',
                'color' => 'color: ' . $backgroundColors[$index % count($colors)] . ';'
            ];
        }

        return [
            'labels' => $labels,
            'dataSet' => $dataSet,
            'chartElement' => $chartElement
        ];
    }

    private function getLatestNegotiations($startDate, $endDate)
    {
        $operations = Operation::with(['property', 'buyerClient', 'ownerClient'])
            ->whereIn('type', $this->closingOperationTypes())
            ->whereBetween(DB::raw($this->operationDateExpression()), [$startDate, $endDate])
            ->orderByRaw($this->operationDateExpression() . ' DESC')
            ->limit(10)
            ->get();

        return [
            'data' => $operations->map(function (Operation $operation) {
                $counterparty = $operation->buyerClient ?: $operation->ownerClient;

                return [
                    'id' => $operation->id,
                    'property' => $operation->property?->title ?: ($operation->external_property_title ?: 'N/A'),
                    'type' => strtoupper((string) ($operation->type ?? 'N/A')),
                    'client' => $counterparty?->name ?: 'N/A',
                    'amount' => (float) ($operation->amount ?: $operation->property_price ?: 0),
                    'date' => Carbon::parse($operation->fecha_cierre ?: $operation->start_date ?: $operation->end_date)->format('Y-m-d'),
                ];
            })
        ];
    }

    private function getTopSellers($startDate, $endDate)
    {
        $topSellers = DB::table('operation_user')
            ->join('operations', 'operation_user.operation_id', '=', 'operations.id')
            ->join('users', 'operation_user.user_id', '=', 'users.id')
            ->whereIn('operations.type', $this->closingOperationTypes())
            ->whereBetween(DB::raw('COALESCE(operations.fecha_cierre, operations.start_date, operations.end_date)'), [$startDate, $endDate])
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                DB::raw('COUNT(DISTINCT operations.id) as closures_count'),
                DB::raw('SUM(COALESCE(operation_user.commission_amount, 0)) as total_commission')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->orderByDesc('closures_count')
            ->orderByDesc('total_commission')
            ->limit(10)
            ->get();

        return [
            'data' => $topSellers->map(function ($seller) {
                $defaultImage = '/images/default-avatar.png'; // Use local default avatar
                return [
                    'id' => $seller->id,
                    'name' => trim(($seller->first_name ?? '') . ' ' . ($seller->last_name ?? '')) ?: 'N/A',
                    'email' => $seller->email ?: 'N/A',
                    'image' => $defaultImage,
                    'closures_count' => $seller->closures_count,
                    'total_commission' => $seller->total_commission,
                ];
            })
        ];
    }

    private function closingOperationTypes(): array
    {
        return ['venta', 'alquiler', 'traspaso', 'reserva'];
    }

    private function operationDateExpression(): string
    {
        return 'COALESCE(fecha_cierre, end_date, start_date, created_at)';
    }

    private function operationAmountExpression(): string
    {
        return 'COALESCE(NULLIF(amount, 0), property_price, 0)';
    }

    private function getSalesOverTime($startDate, $endDate)
    {
        $diffInDays = $startDate->diffInDays($endDate);

        if ($diffInDays <= 31) {
            // Daily grouping
            $groupBy = DB::raw('DATE(' . $this->operationDateExpression() . ')');
            $format = '%Y-%m-%d';
        } elseif ($diffInDays <= 365) {
            // Weekly grouping
            $groupBy = DB::raw('YEARWEEK(' . $this->operationDateExpression() . ')');
            $format = '%Y-W%u';
        } else {
            // Monthly grouping
            $groupBy = DB::raw('DATE_FORMAT(' . $this->operationDateExpression() . ', "%Y-%m")');
            $format = '%Y-%m';
        }

        $salesData = Operation::select(
            DB::raw("DATE_FORMAT(" . $this->operationDateExpression() . ", '$format') as period"),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(' . $this->operationAmountExpression() . ') as revenue')
        )
            ->whereIn('type', $this->closingOperationTypes())
            ->whereBetween(DB::raw($this->operationDateExpression()), [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $captacionesData = Property::select(
            DB::raw("DATE_FORMAT(created_at, '$format') as period"),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('approved_by')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Merge all periods and create a mapping
        $allPeriods = $salesData->pluck('period')
            ->merge($captacionesData->pluck('period'))
            ->unique()
            ->sort()
            ->values();

        // Create mappings for quick lookup
        $salesMap = $salesData->pluck('count', 'period')->toArray();
        $captacionesMap = $captacionesData->pluck('count', 'period')->toArray();

        // Build aligned arrays
        $labels = [];
        $salesCounts = [];
        $captacionesCounts = [];

        foreach ($allPeriods as $period) {
            $labels[] = $period;
            $salesCounts[] = $salesMap[$period] ?? 0;
            $captacionesCounts[] = $captacionesMap[$period] ?? 0;
        }

        $chartData = [
            [
                'title' => 'Cierres',
                'pointRadius' => 0,
                'borderWidth' => 2,
                'borderColor' => 'rgba(240, 84, 84, 0.8)',
                'backgroundColor' => 'rgba(240, 84, 84, 0.8)',
                'data' => $salesCounts
            ],
            [
                'title' => 'Captaciones',
                'pointRadius' => 0,
                'borderWidth' => 2,
                'borderColor' => 'rgba(14, 73, 181, 0.8)',
                'backgroundColor' => 'rgba(14, 73, 181, 0.8)',
                'data' => $captacionesCounts
            ]
        ];

        return [
            'labels' => $labels,
            'chartData' => $chartData,
            'totalSales' => array_sum($salesCounts),
            'totalCaptaciones' => array_sum($captacionesCounts),
        ];
    }

    /**
     * Aggregate total clients grouped by their source (medio de captación).
     * Used for the dashboard donut chart.
     */
    public function getClientsBySource($startDate = null, $endDate = null)
    {
        $colors = ['#713bdb', '#348cd4', '#f75320', '#4caf50', '#ff9800', '#e91e63', '#00bcd4', '#8bc34a'];

        $query = DB::table('clients')
            ->select('source', DB::raw('COUNT(*) as total'))
            ->groupBy('source');

        if ($startDate) {
            $query->whereDate('date', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->whereDate('date', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $rows = $query->get();

        $labels     = [];
        $data       = [];
        $bgColors   = [];

        foreach ($rows as $i => $row) {
            $labels[]   = $row->source ?: 'Sin especificar';
            $data[]     = (int) $row->total;
            $bgColors[] = $colors[$i % count($colors)];
        }

        return [
            'labels'     => $labels,
            'data'       => $data,
            'colors'     => $bgColors,
            'total'      => array_sum($data),
        ];
    }
}
