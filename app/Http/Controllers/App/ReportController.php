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
        $user = auth()->user();
        $userId = $request->get('user_id');

        // If no user_id provided: aggregate all advisors
        if (!$userId) {
            return $this->getAllAdvisorsReport();
        }

        $advisor = User::findOrFail($userId);

        return response()->json([
            'advisor' => [
                'id' => $advisor->id,
                'name' => $advisor->full_name,
            ],
            'metrics' => [
                'sales_count' => $this->getSalesCount($userId),
                'reservations_count' => $this->getReservationsCount($userId),
                'properties_count' => $this->getPropertiesCount($userId),
                'demonstrations_count' => $this->getDemonstrationsCount($userId),
                'closures_count' => $this->getClosuresCount($userId),
                'activities_by_type' => $this->getActivitiesByType($userId),
                'total_activities' => $this->getTotalActivities($userId),
                'total_advisor_commission' => $this->getTotalAdvisorCommission($userId),
            ]
        ]);
    }

    /**
     * Aggregate report for ALL advisors combined
     */
    private function getAllAdvisorsReport()
    {
        return response()->json([
            'advisor' => [
                'id' => null,
                'name' => 'Todos los Asesores',
            ],
            'metrics' => [
                'sales_count' => DB::table('sales')->count(),
                'reservations_count' => DB::table('operations')
                    ->where('type', 'reserva')
                    ->count(),
                'properties_count' => DB::table('properties')
                    ->whereNotNull('approved_by')
                    ->count(),
                'demonstrations_count' => DB::table('activities')
                    ->where('type', 'demostración')
                    ->count(),
                'closures_count' => DB::table('activities')
                    ->whereIn('type', ['venta', 'reserva', 'alquiler'])
                    ->count(),
                'activities_by_type' => DB::table('activities')
                    ->select('type', DB::raw('COUNT(*) as count'))
                    ->groupBy('type')
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->type => $item->count]),
                'total_activities' => DB::table('activities')->count(),
                'total_advisor_commission' => DB::table('operation_user')
                    ->sum('commission_amount'),
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
        $user = auth()->user();

        /* if (!$user->hasRole('Admin') && !$user->hasRole('Administrator')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }*/

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

    private function getTotalAdvisorCommission($userId)
    {
        return DB::table('operation_user')
            ->where('user_id', $userId)
            ->sum('commission_amount');
    }

    private function getSalesCount($userId)
    {
        // Contar ventas donde el usuario es seller_id
        return DB::table('sales')
            ->where('seller_id', $userId)
            ->count();
    }

    private function getReservationsCount($userId)
    {
        // Contar operaciones de tipo 'reserva' asociadas al usuario
        return DB::table('operations')
            ->join('operation_user', 'operations.id', '=', 'operation_user.operation_id')
            ->where('operations.type', 'reserva')
            ->where('operation_user.user_id', $userId)
            ->count();
    }

    private function getPropertiesCount($userId)
    {
        // Contar propiedades creadas por el usuario Y aprobadas (captaciones aprobadas)
        return DB::table('properties')
            ->where('created_by', $userId)
            ->whereNotNull('approved_by')
            ->count();
    }

    private function getActivitiesByType($userId)
    {
        // Agrupar actividades por tipo
        return DB::table('activities')
            ->select('type', DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->type => $item->count];
            });
    }

    private function getDemonstrationsCount($userId)
    {
        // Contar actividades de tipo 'demostración'
        return DB::table('activities')
            ->where('user_id', $userId)
            ->where('type', 'demostración')
            ->count();
    }

    private function getClosuresCount($userId)
    {
        // Contar cierres: ventas + reservas + alquileres
        return DB::table('activities')
            ->where('user_id', $userId)
            ->whereIn('type', ['venta', 'reserva', 'alquiler'])
            ->count();
    }

    private function getTotalActivities($userId)
    {
        // Contar todas las actividades del usuario
        return DB::table('activities')
            ->where('user_id', $userId)
            ->count();
    }
}
