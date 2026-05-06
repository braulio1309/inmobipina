<?php

namespace App\Http\Controllers\App\SamplePage;

use App\Http\Controllers\Controller;
use App\Models\App\SamplePage\Report;
use App\Models\Core\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return Report::paginate(request('per_page', 10));
    }

    /**
     * Get top 10 sellers report with date range filter
     * Returns sellers with most sales and their total amounts
     */
    public function topSellers(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $user = auth()->user();

        // Query to get top sellers
        $query = DB::table('sales')
            ->join('users', 'sales.seller_id', '=', 'users.id')
            ->select(
                'users.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as name"),
                DB::raw('COUNT(sales.id) as count'),
                DB::raw('SUM(sales.total_amount) as value')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name');

        // Non-admin users can only see their own data
        if (!$user->isAdmin()) {
            $query->where('sales.seller_id', $user->id);
        }

        // Apply date filters if provided
        if ($startDate) {
            $query->where('sales.date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('sales.date', '<=', $endDate);
        }

        // Get all sellers ordered by sales count (no limit to show all in table)
        $topSellers = $query
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'data' => $topSellers->map(function ($seller) {
                return [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'count' => (int) $seller->count,
                    'value' => (float) $seller->value
                ];
            })
        ]);
    }

    /**
     * Get advisor commissions sorted from highest to lowest
     */
    public function advisorCommissions(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $user = auth()->user();

        $query = DB::table('operation_user')
            ->join('users', 'operation_user.user_id', '=', 'users.id')
            ->join('operations', 'operation_user.operation_id', '=', 'operations.id')
            ->select(
                'users.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as name"),
                DB::raw('COUNT(operation_user.operation_id) as count'),
                DB::raw('SUM(operation_user.commission_amount) as value')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name');

        if (!$user->isAdmin()) {
            $query->where('operation_user.user_id', $user->id);
        }

        if ($startDate) {
            $query->where('operations.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('operations.created_at', '<=', $endDate . ' 23:59:59');
        }

        $results = $query->orderByDesc('value')->get();

        return response()->json([
            'data' => $results->map(function ($row) {
                return [
                    'id'    => $row->id,
                    'name'  => $row->name,
                    'count' => (int) $row->count,
                    'value' => (float) $row->value,
                ];
            })
        ]);
    }

    /**
     * Overview stats: sales count, captaciones count, reservations count, company commission
     */
    public function overviewStats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end   = $endDate   ? Carbon::parse($endDate)->endOfDay()     : Carbon::now()->endOfDay();

        $salesCount = DB::table('sales')
            ->whereBetween('date', [$start, $end])
            ->count();

        $captacionesCount = DB::table('properties')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('approved_by')
            ->count();

        $reservationsCount = DB::table('operations')
            ->where('type', 'reserva')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $companyCommission = DB::table('operations')
            ->whereIn('type', ['venta', 'reserva'])
            ->whereBetween('created_at', [$start, $end])
            ->sum('company_commission_amount');

        return response()->json([
            'new_candidates'  => $salesCount,
            'moved_forward'   => $captacionesCount,
            'hired'           => $reservationsCount,
            'active_jobs'     => round((float) $companyCommission, 2),
        ]);
    }

    /**
     * Performance chart: company commission earnings over time in date range
     */
    public function performanceChart(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end   = $endDate   ? Carbon::parse($endDate)->endOfDay()     : Carbon::now()->endOfDay();

        $diffInDays = $start->diffInDays($end);

        if ($diffInDays <= 31) {
            $format = '%Y-%m-%d';
        } elseif ($diffInDays <= 365) {
            $format = '%Y-W%u';
        } else {
            $format = '%Y-%m';
        }

        $data = DB::table('operations')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$format') as period"),
                DB::raw('SUM(company_commission_amount) as total')
            )
            ->whereIn('type', ['venta', 'reserva'])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json([
            'labels'  => $data->pluck('period')->values(),
            'values'  => $data->pluck('total')->map(fn($v) => round((float) $v, 2))->values(),
        ]);
    }

    /**
     * Activities grouped by type for the selected date range
     */
    public function activitiesByType(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end   = $endDate   ? Carbon::parse($endDate)->endOfDay()     : Carbon::now()->endOfDay();

        $activities = DB::table('activities')
            ->select('type', DB::raw('COUNT(*) as total_candidates'))
            ->whereBetween('date', [$start, $end])
            ->groupBy('type')
            ->orderByDesc('total_candidates')
            ->get();

        return response()->json(
            $activities->map(fn($a) => [
                'name'             => $a->type ?: 'Sin Tipo',
                'total_candidates' => (int) $a->total_candidates,
            ])->values()
        );
    }

    /**
     * Top advisors by total activities count in date range
     */
    public function topAdvisorsActivities(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end   = $endDate   ? Carbon::parse($endDate)->endOfDay()     : Carbon::now()->endOfDay();

        $advisors = DB::table('activities')
            ->join('users', 'activities.user_id', '=', 'users.id')
            ->select(
                'users.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as name"),
                DB::raw('COUNT(activities.id) as total_candidates')
            )
            ->whereBetween('activities.date', [$start, $end])
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('total_candidates')
            ->limit(10)
            ->get();

        return response()->json(
            $advisors->map(fn($a) => [
                'name'             => $a->name,
                'total_candidates' => (int) $a->total_candidates,
            ])->values()
        );
    }
}
