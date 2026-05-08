<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Core\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Obtener reportes del asesor
     * - Si es admin, puede filtrar por user_id
     * - Si no se selecciona asesor, agrega datos de todos los asesores
     */
    public function getAdvisorReports(Request $request)
    {
        /** @var \App\Models\Core\Auth\User $user */
        $user = auth()->user();
        $userId = $request->get('user_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Non-admin users can only see their own reports
        if (!$user->isAdmin()) {
            $userId = $user->id;
        }

        // If no user_id provided (admin with no filter): aggregate all advisors
        if (!$userId) {
            return $this->getAllAdvisorsReport($startDate, $endDate);
        }

        $advisor = User::findOrFail($userId);

        return response()->json([
            'advisor' => [
                'id' => $advisor->id,
                'name' => $advisor->full_name,
            ],
            'metrics' => [
                'sales_count' => $this->getSalesCount($userId, $startDate, $endDate),
                'reservations_count' => $this->getReservationsCount($userId, $startDate, $endDate),
                'properties_count' => $this->getPropertiesCount($userId, $startDate, $endDate),
                'demonstrations_count' => $this->getDemonstrationsCount($userId, $startDate, $endDate),
                'closures_count' => $this->getClosuresCount($userId, $startDate, $endDate),
                'activities_by_type' => $this->getActivitiesByType($userId, $startDate, $endDate),
                'total_activities' => $this->getTotalActivities($userId, $startDate, $endDate),
                'total_advisor_commission' => $this->getTotalAdvisorCommission($userId, $startDate, $endDate),
            ]
        ]);
    }

    /**
     * Aggregate report for ALL advisors combined
     */
    private function getAllAdvisorsReport($startDate = null, $endDate = null)
    {
        $salesQuery = DB::table('sales');
        $reservationsQuery = DB::table('operations')->where('type', 'reserva');
        $propertiesQuery = DB::table('properties')->whereNotNull('approved_by');
        $demonstrationsQuery = DB::table('activities')->where('type', 'demostración');
        $closuresQuery = DB::table('activities')->whereIn('type', ['venta', 'reserva', 'alquiler']);
        $activitiesByTypeQuery = DB::table('activities')
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type');
        $totalActivitiesQuery = DB::table('activities');
        $commissionQuery = DB::table('operation_user');

        $this->applyDateRange($salesQuery, 'created_at', $startDate, $endDate);
        $this->applyDateRange($reservationsQuery, 'created_at', $startDate, $endDate);
        $this->applyDateRange($propertiesQuery, 'created_at', $startDate, $endDate);
        $this->applyDateRange($demonstrationsQuery, 'created_at', $startDate, $endDate);
        $this->applyDateRange($closuresQuery, 'created_at', $startDate, $endDate);
        $this->applyDateRange($activitiesByTypeQuery, 'created_at', $startDate, $endDate);
        $this->applyDateRange($totalActivitiesQuery, 'created_at', $startDate, $endDate);
        $this->applyDateRange($commissionQuery, 'created_at', $startDate, $endDate);

        return response()->json([
            'advisor' => [
                'id' => null,
                'name' => 'Todos los Asesores',
            ],
            'metrics' => [
                'sales_count' => $salesQuery->count(),
                'reservations_count' => $reservationsQuery->count(),
                'properties_count' => $propertiesQuery->count(),
                'demonstrations_count' => $demonstrationsQuery->count(),
                'closures_count' => $closuresQuery->count(),
                'activities_by_type' => $activitiesByTypeQuery->get()
                    ->mapWithKeys(fn($item) => [$item->type => $item->count]),
                'total_activities' => $totalActivitiesQuery->count(),
                'total_advisor_commission' => $commissionQuery->sum('commission_amount'),
            ]
        ]);
    }

    /**
     * Obtener lista de asesores (solo para admins)
     * 
     * Note: Role names 'Asesor', 'Advisor', 'Agent' are checked to identify advisors.
     * Also includes users with sales or properties for comprehensive reporting.
     */
    public function getAdvisors(Request $request)
    {
        /** @var \App\Models\Core\Auth\User $user */
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $advisors = User::select('id', 'first_name', 'last_name') // 1. Seleccionamos las columnas reales
            ->where(function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['Asesor', 'Advisor', 'Agent']);
                })
                    ->orWhereHas('sales')
                    ->orWhereHas('properties');
            })
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) { // 2. "Mapeamos" para crear el campo name limpio
                return [
                    'id' => $user->id,
                    // trim() elimina espacios extra si no tiene apellido
                    'value' => trim($user->first_name . ' ' . $user->last_name) ?: 'Sin Nombre (' . $user->id . ')'
                ];
            });

        return response()->json($advisors);
    }

    private function getTotalAdvisorCommission($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('operation_user')
            ->where('user_id', $userId);

        $this->applyDateRange($query, 'created_at', $startDate, $endDate);

        return $query->sum('commission_amount');
    }

    private function getSalesCount($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('sales')
            ->where('seller_id', $userId);

        $this->applyDateRange($query, 'created_at', $startDate, $endDate);

        return $query->count();
    }

    private function getReservationsCount($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('operations')
            ->join('operation_user', 'operations.id', '=', 'operation_user.operation_id')
            ->where('operations.type', 'reserva')
            ->where('operation_user.user_id', $userId);

        $this->applyDateRange($query, 'operations.created_at', $startDate, $endDate);

        return $query->count();
    }

    private function getPropertiesCount($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('properties')
            ->where('created_by', $userId)
            ->whereNotNull('approved_by');

        $this->applyDateRange($query, 'created_at', $startDate, $endDate);

        return $query->count();
    }

    private function getActivitiesByType($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('activities')
            ->select('type', DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->groupBy('type');

        $this->applyDateRange($query, 'created_at', $startDate, $endDate);

        return $query->get()
            ->mapWithKeys(function ($item) {
                return [$item->type => $item->count];
            });
    }

    private function getDemonstrationsCount($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('activities')
            ->where('user_id', $userId)
            ->where('type', 'demostración');

        $this->applyDateRange($query, 'created_at', $startDate, $endDate);

        return $query->count();
    }

    private function getClosuresCount($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('activities')
            ->where('user_id', $userId)
            ->whereIn('type', ['venta', 'reserva', 'alquiler']);

        $this->applyDateRange($query, 'created_at', $startDate, $endDate);

        return $query->count();
    }

    private function getTotalActivities($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('activities')
            ->where('user_id', $userId);

        $this->applyDateRange($query, 'created_at', $startDate, $endDate);

        return $query->count();
    }

    private function applyDateRange($query, $column, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate($column, '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate($column, '<=', $endDate);
        }

        return $query;
    }
}
