<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Filters\Common\Auth\ClientFilter as AppUserFilter;
use App\Filters\Core\ClientFilter;
use App\Services\Core\Auth\ClientService;

class ClientController extends Controller
{

    public function __construct(ClientService $Transaction, ClientFilter $filter)
    {
        $this->service = $Transaction;
        $this->filter = $filter;
    }

    public function listado()
    {
        $user = Auth()->user();
        return (new AppUserFilter(
            $this->service
                ->filters($this->filter)
                ->latest()
        ))->filter()
            ->paginate(request()->get('per_page', 10));
    }

    public function create(Request $request)
    {
        $Client = Client::create($request->all());
        return created_responses('Client');
    }

    public function edit(Request $request, $id)
    {

        $Client = Client::where('id', $id)->first();
        $Client->update($request->all());

        return created_responses('Transaction');
    }

    public function show(Client $Client)
    {
        return response()->json($Client);
    }
}
