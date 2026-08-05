<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Property;
use App\Filters\Common\Auth\ActivityFilter as AppUserFilter;
use App\Filters\Core\ActivityFilter;
use App\Services\Core\Auth\ActivityService;
use App\Exports\ActivityExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;


class ActivityController extends Controller
{
    protected function normalizeActivityDate($date)
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->startOfDay();
        }

        $date = trim((string) $date);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        }

        return Carbon::parse($date)->startOfDay();
    }

    public function __construct(ActivityService $Transaction, ActivityFilter $filter)
    {
        $this->service = $Transaction;
        $this->filter = $filter;
    }

    public function listado()
    {
        $user = Auth()->user();
        return (new AppUserFilter(
            $this->service->with('user')
                ->filters($this->filter)
                ->latest()
        ))->filter()
            ->paginate(request()->get('per_page', 10));
    }

    public function formData()
    {
        $properties = Property::query()
            ->select('id', 'title', 'address', 'status')
            ->orderBy('title')
            ->get()
            ->map(function ($property) {
                $title = trim((string) ($property->title ?? ''));
                if ($title === '') {
                    $title = 'Propiedad #' . $property->id;
                }

                if (mb_strlen($title) > 60) {
                    $title = mb_substr($title, 0, 60) . '...';
                }

                return [
                    'id' => (string) $property->id,
                    'value' => $title,
                    'title' => $property->title,
                    'address' => $property->address,
                    'status' => $property->status,
                ];
            })
            ->values();

        return response()->json([
            'properties' => $properties,
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'result' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['demostración', 'captación', 'publicidad', 'venta', 'alquiler', 'reserva'])],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,dng', 'max:5120'],
        ]);

        $userId = Auth::id();
        $activityDate = $this->normalizeActivityDate($validated['date']);

        $duplicateQuery = Activity::query()
            ->where('user_id', $userId)
            ->where('type', $validated['type'])
            ->whereDate('date', $activityDate->toDateString())
            ->where('created_at', '>=', now()->subMinutes(3));

        $duplicateQuery->where('description', $validated['description'] ?? null);
        $duplicateQuery->where('result', $validated['result'] ?? null);

        if (!empty($validated['property_id'])) {
            $duplicateQuery->where('property_id', $validated['property_id']);
        } else {
            $duplicateQuery->whereNull('property_id');
        }

        if (!empty($validated['client_id'])) {
            $duplicateQuery->where('client_id', $validated['client_id']);
        } else {
            $duplicateQuery->whereNull('client_id');
        }

        if ($duplicateQuery->exists()) {
            return response()->json([
                'message' => 'Esta actividad ya fue registrada recientemente. Evitamos un duplicado.',
            ], 200);
        }

        try {
            DB::beginTransaction();

            $data = [
                'user_id' => $userId,
                'result' => $validated['result'] ?? null,
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'date' => $activityDate,
                'client_id' => $validated['client_id'] ?? null,
                'property_id' => $validated['property_id'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ];

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('activity_images', 'public');
            }

            Activity::create($data);

            DB::commit();

            return created_responses('Activity');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear actividad', [
                'message' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'message' => 'No se pudo guardar la actividad. Intenta nuevamente.',
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        $validated = $request->validate([
            'result' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['demostración', 'captación', 'publicidad', 'venta', 'alquiler', 'reserva'])],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,dng', 'max:5120'],
        ]);

        $activity = Activity::where('id', $id)->firstOrFail();

        try {
            DB::beginTransaction();

            $data = [
                'result' => $validated['result'] ?? null,
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'date' => $this->normalizeActivityDate($validated['date']),
                'client_id' => $validated['client_id'] ?? null,
                'property_id' => $validated['property_id'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ];

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('activity_images', 'public');
            }

            $activity->update($data);

            DB::commit();

            return created_responses('Activity');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al editar actividad', [
                'message' => $e->getMessage(),
                'activity_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'No se pudo actualizar la actividad. Intenta nuevamente.',
            ], 500);
        }
    }

    public function show(Activity $Activity)
    {
        return response()->json($Activity->load('user', 'client', 'property'));
    }

    public function destroy($id)
    {
        $activity = Activity::query()->findOrFail($id);

        try {
            $activity->delete();

            return response()->json([
                'message' => 'Actividad eliminada correctamente.',
            ]);
        } catch (Throwable $e) {
            Log::error('Error al eliminar actividad', [
                'message' => $e->getMessage(),
                'activity_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'No se pudo eliminar la actividad. Intenta nuevamente.',
                 'message2' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $fileName = 'actividades_' . now()->format('Y-m-d_H-i') . '.xlsx';
        return Excel::download(new ActivityExport($request), $fileName);
    }
}

