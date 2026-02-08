<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Core\Auth\User;
use App\Models\Operation;
use App\Models\Property;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get advisor metrics
     * Endpoint: GET /api/reports/advisor-metrics?user_id={id}
     */
    public function getAdvisorMetrics(Request $request)
    {
        $user = Auth::user();
        $userId = $request->input('user_id');

        // Authorization: Regular advisors can only see their own metrics
        if (!$user->isAdmin() && $userId != $user->id) {
            return response()->json([
                'message' => 'Unauthorized to view this advisor\'s metrics'
            ], 403);
        }

        // If no user_id provided, use authenticated user's id
        if (!$userId) {
            $userId = $user->id;
        }

        // Validate user exists
        $advisor = User::find($userId);
        if (!$advisor) {
            return response()->json([
                'message' => 'Advisor not found'
            ], 404);
        }

        // Get sales count
        $salesCount = Sale::where('seller_id', $userId)->count();

        // Get reservations count (operations with type='reserva' and user in operation_user pivot)
        $reservationsCount = Operation::where('type', 'reserva')
            ->whereHas('sellers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        // Get properties count (captaciones - properties created by this user)
        $propertiesCount = Property::where('created_by', $userId)->count();

        // Get activities by type
        $activitiesByType = Activity::where('user_id', $userId)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Ensure all activity types are present (even if count is 0)
        $activityTypes = ['demostración', 'captación', 'venta', 'alquiler', 'reserva'];
        foreach ($activityTypes as $type) {
            if (!isset($activitiesByType[$type])) {
                $activitiesByType[$type] = 0;
            }
        }

        return response()->json([
            'advisor' => [
                'id' => $advisor->id,
                'name' => $advisor->getFullName()
            ],
            'metrics' => [
                'sales_count' => $salesCount,
                'reservations_count' => $reservationsCount,
                'properties_count' => $propertiesCount,
                'activities_by_type' => $activitiesByType
            ]
        ]);
    }

    /**
     * Get all advisors list (only for admins)
     * Endpoint: GET /api/reports/advisors
     */
    public function getAdvisors(Request $request)
    {
        $user = Auth::user();

        // Only admins can access this endpoint
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        // Get all users (advisors)
        $advisors = User::select('id', 'first_name', 'last_name', 'email')
            ->get()
            ->map(function ($advisor) {
                return [
                    'id' => $advisor->id,
                    'name' => $advisor->getFullName(),
                    'email' => $advisor->email
                ];
            });

        return response()->json([
            'advisors' => $advisors
        ]);
    }
}
