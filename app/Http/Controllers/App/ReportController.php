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
     * - Si es asesor, solo ve sus propios datos
     */
    public function getAdvisorReports(Request $request)
    {
        $user = auth()->user();
        $userId = $request->get('user_id');
        
        // Si no es admin, forzar que vea solo sus datos
        if (!$user->hasRole('Admin') && !$user->hasRole('Administrator')) {
            $userId = $user->id;
        }
        
        // Si no se especifica usuario, usar el autenticado
        if (!$userId) {
            $userId = $user->id;
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
                'activities_by_type' => $this->getActivitiesByType($userId),
            ]
        ]);
    }
    
    /**
     * Obtener lista de asesores (solo para admins)
     */
    public function getAdvisors(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('Admin') && !$user->hasRole('Administrator')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $advisors = User::select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
            ->whereHas('roles', function($query) {
                $query->whereIn('name', ['Asesor', 'Advisor', 'Agent']);
            })
            ->orWhereHas('sales')
            ->orWhereHas('properties')
            ->orderBy('first_name')
            ->get();
        
        return response()->json($advisors);
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
        // Contar propiedades creadas por el usuario (captaciones)
        return DB::table('properties')
            ->where('created_by', $userId)
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
}
