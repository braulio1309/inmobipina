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
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;


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
                $address = trim((string) ($property->address ?? ''));

                if (mb_strlen($address) > 30) {
                    $address = mb_substr($address, 0, 30) . '...';
                }

                return [
                    'id' => (string) $property->id,
                    'value' => $address !== '' ? trim($title . ' - ' . $address) : $title,
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
        $data = $request->only(['result', 'type', 'description', 'date', 'client_id', 'property_id']);
        $data['user_id'] = Auth::user()->id;
        if (!empty($data['date'])) {
            $data['date'] = $this->normalizeActivityDate($data['date']);
        }

        // Handle geolocation
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $data['latitude'] = $request->input('latitude');
            $data['longitude'] = $request->input('longitude');
        }

        $Activity = Activity::create($data);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('activity_images', 'public');
            $Activity->update(['image_path' => $path]);
        }

        return created_responses('Activity');
    }

    public function edit(Request $request, $id)
    {
        $Activity = Activity::where('id', $id)->firstOrFail();
        $data = $request->only(['result', 'type', 'description', 'date', 'client_id', 'property_id']);

        if (!empty($data['date'])) {
            $data['date'] = $this->normalizeActivityDate($data['date']);
        }

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $data['latitude'] = $request->input('latitude');
            $data['longitude'] = $request->input('longitude');
        }

        // Handle image upload on edit
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('activity_images', 'public');
            $data['image_path'] = $path;
        }

        $Activity->update($data);

        return created_responses('Activity');
    }

    public function show(Activity $Activity)
    {
        return response()->json($Activity->load('user', 'client', 'property'));
    }

    public function export(Request $request)
    {
        $fileName = 'actividades_' . now()->format('Y-m-d_H-i') . '.xlsx';
        return Excel::download(new ActivityExport($request), $fileName);
    }
}

