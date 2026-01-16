<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Filters\Common\Auth\PropertyFilter as AppUserFilter;
use App\Filters\Core\PropertyFilter;
use App\Services\Core\Auth\PropertyService;

class PropertyController extends Controller
{

    public function __construct(PropertyService $Transaction, PropertyFilter $filter)
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
        $Property = Property::create($request->all());
        return created_responses('Property');
    }

    public function edit(Request $request, $id)
    {

        $Property = Property::where('id', $id)->first();
        $Property->update($request->all());

        return created_responses('Transaction');
    }

    public function show(Property $Property)
    {
        return response()->json($Property);
    }
}
