<?php

namespace App\Http\Controllers\App\SamplePage;

use App\Http\Controllers\Controller;
use App\Models\App\SamplePage\Report;
use App\Models\Core\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
