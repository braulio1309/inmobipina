<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Property;
use App\Models\Core\Auth\User;
use App\Filters\Common\Auth\ClientFilter as AppUserFilter;
use App\Filters\Core\ClientFilter;
use App\Services\Core\Auth\ClientService;
use App\Exports\ClientExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{

    public function __construct(ClientService $Transaction, ClientFilter $filter)
    {
        $this->service = $Transaction;
        $this->filter = $filter;
    }

    public function listado()
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $clients */
        $clients = (new AppUserFilter(
            $this->service
                ->with(['advisor'])
                ->filters($this->filter)
                ->latest()
        ))->filter()
            ->paginate(request()->get('per_page', 10));

        $clients->getCollection()->transform(function ($client) {
            $client->name = trim((string) ($client->name ?? ''));
            $client->display_name = $client->name !== '' ? $client->name : 'Sin nombre';
            $client->date = $client->date ? $client->date->format('Y-m-d') : null;

            $client->advisor_name = $client->advisor
                ? trim(($client->advisor->first_name ?? '') . ' ' . ($client->advisor->last_name ?? ''))
                : '—';

            return $client;
        });

        return $clients;
    }

    public function export(Request $request)
    {
        $fileName = 'clientes_' . now()->format('Y-m-d_H-i') . '.xlsx';
        return Excel::download(new ClientExport($request), $fileName);
    }

    public function formData()
    {
        $users = User::query()
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user) => [
                'id' => (string) $user->id,
                'value' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ('Usuario #' . $user->id),
            ])
            ->values();

        $properties = Property::query()
            ->select('id', 'title', 'address', 'status')
            ->orderBy('title')
            ->get()
            ->map(fn ($property) => [
                'id' => (string) $property->id,
                'value' => trim(($property->title ?? '') . ' - ' . ($property->address ?? '')),
                'title' => $property->title,
                'address' => $property->address,
                'status' => $property->status,
            ])
            ->values();

        return response()->json([
            'users' => $users,
            'properties' => $properties,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'date', 'notes', 'source', 'status', 'assigned_to']);
        /** @var User $authUser */
        $authUser = Auth::user();

        $data['user_id'] = $authUser->id;
        $data['assigned_to'] = $authUser->isAdmin()
            ? $this->normalizeAssignedTo($data['assigned_to'] ?? null)
            : $authUser->id;

        if (empty($data['status'])) {
            $data['status'] = 'potencial';
        }
        $Client = Client::create($data);

        $Client->properties()->sync($this->normalizePropertyIds($request->input('property_ids', [])));

        return created_responses('Client');
    }

    public function edit(Request $request, $id)
    {
        $Client = Client::where('id', $id)->first();
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'date', 'notes', 'source', 'status', 'assigned_to']);
        /** @var User $authUser */
        $authUser = Auth::user();

        $data['assigned_to'] = $authUser->isAdmin()
            ? $this->normalizeAssignedTo($data['assigned_to'] ?? null)
            : $authUser->id;

        $Client->update($data);
        $Client->properties()->sync($this->normalizePropertyIds($request->input('property_ids', [])));

        return created_responses('Transaction');
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:potencial,no potencial,atendido,cerrado',
        ]);

        $client = Client::findOrFail($id);
        $client->update(['status' => $request->status]);

        return response()->json(['message' => 'Estatus actualizado correctamente.']);
    }

    public function show(Client $Client)
    {
        return response()->json($Client->load('properties:id,title,address,status'));
    }

    private function normalizePropertyIds($propertyIds): array
    {
        if (!is_array($propertyIds)) {
            return [];
        }

        return collect($propertyIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeAssignedTo($assignedTo): ?int
    {
        if ($assignedTo === null || $assignedTo === '') {
            return null;
        }

        return (int) $assignedTo;
    }
}
