<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Core\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function excludedReportUserNames(): array
    {
        return [
           
        ];
    }

    private function applyExcludedReportUsers($query, string $firstNameColumn = 'users.first_name', string $lastNameColumn = 'users.last_name')
    {
        $excludedNames = $this->excludedReportUserNames();
        if (empty($excludedNames)) {
            return $query;
        }

        $placeholders = implode(', ', array_fill(0, count($excludedNames), '?'));

        return $query->whereRaw(
            "LOWER(TRIM(CONCAT($firstNameColumn, ' ', COALESCE($lastNameColumn, '')))) NOT IN ($placeholders)",
            $excludedNames
        );
    }

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
        $salesQuery = DB::table('operations')->whereIn('type', ['venta', 'traspaso']);
        $reservationsQuery = DB::table('operations')->where('type', 'reserva');
        $propertiesQuery = DB::table('properties')->whereNotNull('approved_by');
        $demonstrationsQuery = DB::table('activities')->where('type', 'demostración');
        $closuresQuery = DB::table('operations')->whereIn('type', ['venta', 'traspaso', 'reserva', 'alquiler']);
        $activitiesByTypeQuery = DB::table('activities')
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type');
        $totalActivitiesQuery = DB::table('activities');
        $commissionQuery = DB::table('operation_user')
            ->join('operations', 'operation_user.operation_id', '=', 'operations.id');
        $commissionQuery->where('operations.type', '!=', 'reserva');

        $this->applyDateRange($salesQuery, DB::raw('COALESCE(fecha_cierre, start_date, end_date)'), $startDate, $endDate);
        $this->applyDateRange($reservationsQuery, DB::raw('COALESCE(fecha_cierre, start_date, end_date)'), $startDate, $endDate);
        $this->applyDateRange($propertiesQuery, 'created_at', $startDate, $endDate);
        $this->applyDateRange($demonstrationsQuery, 'date', $startDate, $endDate);
        $this->applyDateRange($closuresQuery, DB::raw('COALESCE(fecha_cierre, start_date, end_date)'), $startDate, $endDate);
        $this->applyDateRange($activitiesByTypeQuery, 'date', $startDate, $endDate);
        $this->applyDateRange($totalActivitiesQuery, 'date', $startDate, $endDate);
        $this->applyDateRange($commissionQuery, DB::raw('COALESCE(operations.fecha_cierre, operations.start_date, operations.end_date)'), $startDate, $endDate);

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

        $advisorsQuery = User::select('id', 'first_name', 'last_name')
            ->where(function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['Asesor', 'Advisor', 'Agent']);
                })
                    ->orWhereHas('sales')
                    ->orWhereHas('properties');
            });

        $this->applyExcludedReportUsers($advisorsQuery, 'first_name', 'last_name');

        $advisors = $advisorsQuery
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'value' => trim($user->first_name . ' ' . $user->last_name) ?: 'Sin Nombre (' . $user->id . ')'
                ];
            });

        return response()->json($advisors);
    }

    private function getTotalAdvisorCommission($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('operation_user')
            ->join('operations', 'operation_user.operation_id', '=', 'operations.id')
            ->where('user_id', $userId)
            ->where('operations.type', '!=', 'reserva');

        $this->applyDateRange($query, DB::raw('COALESCE(operations.fecha_cierre, operations.start_date, operations.end_date)'), $startDate, $endDate);

        return $query->sum('commission_amount');
    }

    private function getSalesCount($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('operations')
            ->join('operation_user', 'operations.id', '=', 'operation_user.operation_id')
            ->whereIn('operations.type', ['venta', 'traspaso'])
            ->where('operation_user.user_id', $userId);

        $this->applyDateRange($query, DB::raw('COALESCE(operations.fecha_cierre, operations.start_date, operations.end_date)'), $startDate, $endDate);

        return $query->distinct('operations.id')->count('operations.id');
    }

    private function getReservationsCount($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('operations')
            ->join('operation_user', 'operations.id', '=', 'operation_user.operation_id')
            ->where('operations.type', 'reserva')
            ->where('operation_user.user_id', $userId);

        $this->applyDateRange($query, DB::raw('COALESCE(operations.fecha_cierre, operations.start_date, operations.end_date)'), $startDate, $endDate);

        return $query->distinct('operations.id')->count('operations.id');
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

        $this->applyDateRange($query, 'date', $startDate, $endDate);

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

        $this->applyDateRange($query, 'date', $startDate, $endDate);

        return $query->count();
    }

    private function getClosuresCount($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('operations')
            ->join('operation_user', 'operations.id', '=', 'operation_user.operation_id')
            ->where('operation_user.user_id', $userId)
            ->whereIn('operations.type', ['venta', 'traspaso', 'reserva', 'alquiler']);

        $this->applyDateRange($query, DB::raw('COALESCE(operations.fecha_cierre, operations.start_date, operations.end_date)'), $startDate, $endDate);

        return $query->distinct('operations.id')->count('operations.id');
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
        if (!$startDate && !$endDate) {
            return $query;
        }

        // Use whereRaw for DB::raw expressions to avoid potential issues
        // with whereDate wrapping raw SQL. For plain column names use whereDate.
        // Note: $column is only ever passed as a hardcoded DB::raw() or a trusted
        // column name string within this class — never from user input.
        if ($column instanceof \Illuminate\Database\Query\Expression) {
            // Allowed COALESCE expressions used internally in this controller
            $allowedExpressions = [
                'COALESCE(fecha_cierre, start_date, end_date)',
                'COALESCE(operations.fecha_cierre, operations.start_date, operations.end_date)',
            ];
            $rawSql = $column->getValue();
            if (!in_array($rawSql, $allowedExpressions, true)) {
                throw new \InvalidArgumentException("Unexpected raw SQL expression in applyDateRange: {$rawSql}");
            }
            if ($startDate) {
                $query->whereRaw('DATE(' . $rawSql . ') >= ?', [$startDate]);
            }
            if ($endDate) {
                $query->whereRaw('DATE(' . $rawSql . ') <= ?', [$endDate]);
            }
        } else {
            if ($startDate) {
                $query->whereDate($column, '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate($column, '<=', $endDate);
            }
        }

        return $query;
    }

    /**
     * Clientes asignados por asesor con breakdown por medio de captación
     */
    public function getClientsByAdvisor(Request $request)
    {
        /** @var \App\Models\Core\Auth\User $authUser */
        $authUser = auth()->user();

        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');
        $userId    = $request->get('user_id');

        if (!$authUser->isAdmin()) {
            $userId = $authUser->id;
        }

        $query = DB::table('clients')
            ->join('users', 'clients.assigned_to', '=', 'users.id')
            ->select(
                'users.id as advisor_id',
                DB::raw("CONCAT(users.first_name, ' ', COALESCE(users.last_name, '')) as advisor_name"),
                'clients.source',
                DB::raw('COUNT(clients.id) as total')
            )
            ->whereNotNull('clients.assigned_to')
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'clients.source')
            ->orderBy('advisor_name');

        $this->applyExcludedReportUsers($query);

        if ($userId) {
            $query->where('users.id', $userId);
        }

        if ($startDate) {
            $query->whereDate('clients.date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('clients.date', '<=', $endDate);
        }

        $rows = $query->get();

        // Group by advisor
        $advisors = [];
        foreach ($rows as $row) {
            $id = $row->advisor_id;
            if (!isset($advisors[$id])) {
                $advisors[$id] = [
                    'id' => $id,
                    'name' => trim($row->advisor_name),
                    'total' => 0,
                    'by_source' => [],
                ];
            }
            $source = $row->source ?: 'Sin especificar';
            $advisors[$id]['total'] += $row->total;
            $advisors[$id]['by_source'][$source] = ($advisors[$id]['by_source'][$source] ?? 0) + $row->total;
        }

        return response()->json(array_values($advisors));
    }
}
