<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Property;
use App\Models\Core\Auth\User;
use App\Filters\Common\Auth\ClientFilter as AppUserFilter;
use App\Filters\Core\ClientFilter;
use App\Services\Core\Auth\ClientService;
use Illuminate\Support\Facades\Auth;

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
                ->filters($this->filter)
                ->latest()
        ))->filter()
            ->paginate(request()->get('per_page', 10));

        $clients->getCollection()->transform(function ($client) {
            $client->name = trim((string) ($client->name ?? ''));
            $client->display_name = $client->name !== '' ? $client->name : 'Sin nombre';

            return $client;
        });

        return $clients;
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
        $data = $request->only(['name', 'email', 'phone', 'notes', 'source', 'status', 'assigned_to']);
        $data['user_id'] = Auth::id();
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
        $data = $request->only(['name', 'email', 'phone', 'notes', 'source', 'status', 'assigned_to']);
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
}

