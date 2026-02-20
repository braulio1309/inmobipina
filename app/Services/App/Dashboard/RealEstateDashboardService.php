<?php

namespace App\Services\App\Dashboard;

use App\Models\Sale;
use App\Models\Exclusivity;
use App\Models\Property;
use App\Models\Activity;
use App\Models\Core\Auth\User;
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
            'latestSales' => $this->getLatestSales($startDate, $endDate),
            'topSellers' => $this->getTopSellers($startDate, $endDate),
            'salesOverTime' => $this->getSalesOverTime($startDate, $endDate),
        ];
    }

    private function getDefaultData($startDate, $endDate)
    {
        // Total sales revenue
        $totalSalesRevenue = Sale::whereBetween('date', [$startDate, $endDate])
            ->sum('total_amount');

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
        $totalCompanyCommission = \DB::table('operations')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('type', ['venta', 'reserva'])
            ->sum('company_commission_amount');

        return [
            ['label' => 'Total Ganancias en Ventas', 'number' => $totalSalesRevenue, 'icon' => 'dollar-sign'],
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

    private function getLatestSales($startDate, $endDate)
    {
        $sales = Sale::with(['property', 'buyer', 'seller'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return [
            'data' => $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'property' => $sale->property ? $sale->property->title : 'N/A',
                    'buyer' => $sale->buyer ? $sale->buyer->name : 'N/A',
                    'seller' => $sale->seller ? $sale->seller->full_name : 'N/A',
                    'amount' => $sale->total_amount,
                    'date' => Carbon::parse($sale->date)->format('Y-m-d'),
                ];
            })
        ];
    }

    private function getTopSellers($startDate, $endDate)
    {
        $topSellers = Sale::select('seller_id', DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(total_amount) as total_revenue'))
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('seller_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        return [
            'data' => $topSellers->map(function ($seller) {
                $user = User::find($seller->seller_id);
                $defaultImage = '/images/default-avatar.png'; // Use local default avatar
                return [
                    'id' => $seller->seller_id,
                    'name' => $user ? $user->full_name : 'N/A',
                    'email' => $user ? $user->email : 'N/A',
                    'image' => $user && $user->profile_picture ? $user->profile_picture->path : $defaultImage,
                    'sales_count' => $seller->sales_count,
                    'total_revenue' => $seller->total_revenue,
                ];
            })
        ];
    }

    private function getSalesOverTime($startDate, $endDate)
    {
        $diffInDays = $startDate->diffInDays($endDate);

        if ($diffInDays <= 31) {
            // Daily grouping
            $groupBy = DB::raw('DATE(date)');
            $format = '%Y-%m-%d';
        } elseif ($diffInDays <= 365) {
            // Weekly grouping
            $groupBy = DB::raw('YEARWEEK(date)');
            $format = '%Y-W%u';
        } else {
            // Monthly grouping
            $groupBy = DB::raw('DATE_FORMAT(date, "%Y-%m")');
            $format = '%Y-%m';
        }

        $salesData = Sale::select(
            DB::raw("DATE_FORMAT(date, '$format') as period"),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->whereBetween('date', [$startDate, $endDate])
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
                'title' => 'Ventas',
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
}
