<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
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
        return (new AppUserFilter(
            $this->service
                ->filters($this->filter)
                ->latest()
        ))->filter()
            ->paginate(request()->get('per_page', 10));
    }

    public function create(Request $request)
    {
        $data = $request->only(['name', 'email', 'phone', 'notes', 'source', 'status', 'assigned_to']);
        $data['user_id'] = Auth::id();
        if (empty($data['status'])) {
            $data['status'] = 'potencial';
        }
        $Client = Client::create($data);
        return created_responses('Client');
    }

    public function edit(Request $request, $id)
    {
        $Client = Client::where('id', $id)->first();
        $data = $request->only(['name', 'email', 'phone', 'notes', 'source', 'status', 'assigned_to']);
        $Client->update($data);

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
        return response()->json($Client);
    }
}

