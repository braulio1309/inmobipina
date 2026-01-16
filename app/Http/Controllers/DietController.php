<?php

namespace App\Http\Controllers;

use App\Models\Diet;
use App\Models\Core\Auth\User;
use Illuminate\Http\Request;
use App\Services\OpenAiDietService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DietController extends Controller
{
    protected $aiService;

    public function __construct(OpenAiDietService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Crear una nueva dieta para un usuario.
     */
    public function generate(Request $request)
    {
        // ✅ Validar los datos entrantes
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // ✅ Obtener el usuario
        $user = User::findOrFail($data['user_id']);

        // ✅ Construir dataset para IA
        $payload = array_merge([
            'name' => $user->name,
            'birth_date' => $user->birth_date,
            'sex' => $user->sex,
            'email' => $user->email,
        ], $data);

        // ✅ Llamar a la IA para generar el contenido
        $dietData = $this->aiService->generateDietContent($payload);

        

        // ✅ Guardar la dieta en BD
        $diet = Diet::create([
            'user_id' => $user->id,
            'title' => 'Plan nutricional personalizado',
            'weight_kg' => $data['weight_kg'] ?? null,
            'height_cm' => $data['height_cm'] ?? null,
            'activity_level' => $data['activity_level'] ?? null,
            'goal' => $data['goal'] ?? null,
            'start_date' => $data['start_date'] ?? now(),
            'allergies' => $data['allergies'] ?? null,
            'preferences' => $data['preferences'] ?? null,
            'observations' => $data['observations'] ?? null,
            'tmb_kcal' => $dietData['tmb_kcal'] ?? null,
            'total_kcal' => $dietData['total_kcal'] ?? null,
            'target_kcal' => $dietData['target_kcal'] ?? null,
            'macros' => $dietData['macros'] ?? null,
            'meal_options' => $dietData['meal_options'] ?? null,
            'criteria' => $dietData['ten_criteria'] ?? null,
            'free_meal_rules' => $dietData['free_meal_rules'] ?? null,
            'supplements' => $dietData['supplements'] ?? null,
            'coach_notes' => $dietData['coach_notes'] ?? ($data['observations'] ?? null),
            'footer' => $dietData['footer'] ?? null,
        ]);

        $pdf = \PDF::loadView('pdf.diet', [
            'user' => $user,
            'diet' => $diet,
            'data' => $dietData,
        ])->setPaper('a4', 'portrait');

        // Guardar PDF en el storage
        $fileName = "dietas/dieta_{$user->id}_" . now()->format('Ymd_His') . ".pdf";
        Storage::put("public/{$fileName}", $pdf->output());

        // Guardar path del PDF en la dieta
        $diet->update(['pdf_path' => "storage/{$fileName}"]);

        return response()->json([
                'status' => true,
                'diet' => $diet
            ], 200);
    }
}
