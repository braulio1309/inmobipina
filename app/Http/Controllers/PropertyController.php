<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Exclusivity;
use App\Filters\Common\Auth\PropertyFilter as AppUserFilter;
use App\Filters\Core\PropertyFilter;
use App\Services\Core\Auth\PropertyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PropertyController extends Controller
{

    public function __construct(PropertyService $Transaction, PropertyFilter $filter)
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
        $data = $request->only([
            'agent_id', 'title', 'description', 'price',
            'bathrooms', 'bedrooms', 'square_meters', 'address',
            'type', 'type_sale', 'status', 'map_lat', 'map_lng',
            'exclusivity', 'created_by', 'approved_by',
        ]);
        $data['created_by'] = Auth::id();

        
        $data['status'] = 'pending';
        unset($data['approved_by']);
       // unset($data['exclusivity']);
    //La data['exclusivity '] me da eerror de Array to string conversion corrigelo por favor haz un proceso para ue no me salte mas ese error
    
        $property = Property::create($data);
        // Save exclusivity contract data if provided
        if ($request->has('exclusivity_data') && is_array($request->exclusivity_data)) {
            $exData = $request->exclusivity_data;
            $exData['property_id'] = $property->id;
            $exData['user_id'] = Auth::id();
            Exclusivity::create($exData);
        }

        return response()->json(['message' => 'Property created successfully.', 'data' => $property], 201);
    }

    public function uploadImages(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        $request->validate([
            'images.*' => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        $saved = [];
        $order = $property->images()->count();
        foreach ($request->file('images', []) as $file) {
            $path = $file->store('property_images', 'public');
            $image = PropertyImage::create([
                'property_id' => $property->id,
                'path'        => $path,
                'order'       => $order++,
            ]);
            $saved[] = $image;
        }

        return response()->json(['message' => 'Images uploaded.', 'data' => $saved], 201);
    }

    public function generateExclusivityPdf($id)
    {
        $property = Property::with('exclusivities')->findOrFail($id);
        $exclusivity = $property->exclusivities()->latest()->first();

        if (!$exclusivity) {
            return response()->json(['message' => 'No exclusivity contract data found.'], 404);
        }

        $data = [
            'propietario' => [
                'nombre' => $exclusivity->propietario_nombre ?? '',
                'ci'     => $exclusivity->propietario_ci ?? '',
                'rif'    => $exclusivity->propietario_rif ?? '',
                'email'  => $exclusivity->propietario_email ?? '',
                'phone'  => $exclusivity->propietario_telefono ?? '',
            ],
            'exclusivity' => $exclusivity,
            'property' => [
                'price'               => $property->price,
                'square_meters'       => $property->square_meters,
                'address'             => $property->address,
                'inmueble_descripcion'=> $exclusivity->inmueble_descripcion ?? '',
            ],
            'fecha_contrato' => $exclusivity->fecha_firma
                ? \Carbon\Carbon::parse($exclusivity->fecha_firma)->locale('es')->isoFormat('DD [de] MMMM [de] YYYY')
                : now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY'),
        ];

        $pdf = Pdf::loadView('pdf.exclusividad', $data);
        $fileName = 'contrato_exclusividad_propiedad_' . $property->id . '.pdf';
        $filePath = 'public/contracts/' . $fileName;

        Storage::put($filePath, $pdf->output());
        $exclusivity->update(['contract_path' => $fileName]);

        return response()->download(storage_path('app/' . $filePath), $fileName);
    }

    public function approve(Request $request, $id)
    {
        $action = $request->input('action');

        if (!in_array($action, ['approve', 'reject'])) {
            return response()->json(['message' => 'Acción no válida.'], 422);
        }

        $property = Property::findOrFail($id);

        if ($action === 'approve') {
            $property->update([
                'status' => 'Disponible',
                'approved_by' => Auth::id(),
            ]);
            return response()->json(['message' => 'Propiedad aprobada exitosamente.']);
        } else {
            $property->update([
                'status' => 'No disponible',
                'approved_by' => null,
            ]);
            return response()->json(['message' => 'Propiedad rechazada.']);
        }
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

