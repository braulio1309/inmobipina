<?php

namespace App\Http\Controllers;

use App\Models\Core\Auth\User;
use App\Models\Diet;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Crea o actualiza un usuario con sus datos personales y físicos.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'sex' => 'nullable|string|max:10',
            'weight_kg' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'activity_level' => 'nullable|string|max:50',
            'goal' => 'nullable|string|max:50',
            'allergies' => 'nullable|string',
            'preferences' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Buscar si ya existe
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            $user = User::create([
                'first_name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('default123'),
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'sex' => $data['gender'] ?? null,
                'weight_kg' => $data['weight_kg'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
                'activity_level' => $data['activity_level'] ?? null,
                'goal' => $data['goal'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'preferences' => $data['preferences'] ?? null,
                'status_id' => 1
            ]);
        } else {
            $user->update($data);
        }

        return response()->json([
            'status' => true,
            'user' => $user
        ], 200);
    }

    public function update(Request $request, $id)
    {
        // Buscar el usuario
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Validación de datos
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:Masculino,Femenino,Otro',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'weight_kg' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'activity_level' => 'nullable|string|max:50',
            'goal' => 'nullable|string|max:50',
            'allergies' => 'nullable|string',
            'preferences' => 'nullable|string',
            'observations' => 'nullable|string',
            'start_date' => 'nullable|date',
        ]);

        try {
            // Actualizar datos del usuario
            $user->update($validated);

            // Si quieres actualizar dietas asociadas, puedes hacerlo aquí
            // Ejemplo: actualizar dietas manuales (opcional)
            if ($request->has('diets')) {
                foreach ($request->diets as $dietData) {
                    if (isset($dietData['id'])) {
                        $diet = Diet::find($dietData['id']);
                        if ($diet) {
                            $diet->update($dietData);
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Usuario actualizado correctamente',
                'user' => $user->load('diets'), // incluye dietas si quieres
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showWithDiets($id)
    {
        try {
            // Trae usuario con todas sus dietas
            $user = User::with('diets')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'user' => $user,
                'diets' => $user->diets, // opcional, ya viene incluido en 'user'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo obtener el usuario o las dietas.',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
