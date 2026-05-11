<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyDocument;
use App\Models\Exclusivity;
use App\Filters\Common\Auth\PropertyFilter as AppUserFilter;
use App\Filters\Core\PropertyFilter;
use App\Services\Core\Auth\PropertyService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PropertyExport;
use Maatwebsite\Excel\Facades\Excel;

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
            'bathrooms', 'bedrooms', 'square_meters', 'parking_spots', 'address',
            'type', 'type_sale', 'status', 'map_lat', 'map_lng',
            'exclusivity', 'created_by', 'approved_by',
        ]);
        $data['created_by'] = Auth::id();

        /** @var \App\Models\Core\Auth\User|null $authUser */
        $authUser = Auth::user();

        if ($authUser && $authUser->isAppAdmin()) {
            $data['status'] = 'Disponible';
            $data['approved_by'] = Auth::id();
        } else {
            $data['status'] = 'pending';
            unset($data['approved_by']);
        }

        // Normaliza el campo exclusivity: puede llegar como array (checkbox) o boolean
        if (isset($data['exclusivity'])) {
            if (is_array($data['exclusivity'])) {
                $data['exclusivity'] = !empty($data['exclusivity']);
            } else {
                $data['exclusivity'] = (bool) $data['exclusivity'];
            }
        }

        $property = Property::create($data);
        // Save exclusivity contract data if provided
        if ($request->has('exclusivity_data') && is_array($request->exclusivity_data)) {
            $exData = $request->exclusivity_data;
            $exData['property_id'] = $property->id;
            $exData['user_id'] = Auth::id();
            Exclusivity::create($exData);
        }

        return response()->json(['message' => 'Propiedad creada exitosamente.', 'data' => $property], 201);
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

        if (!Storage::exists($filePath)) {
            return response()->json(['message' => 'El archivo del contrato no fue encontrado.'], 404);
        }

        return Storage::download(
            $filePath,
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
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
        $property = Property::where('id', $id)->firstOrFail();
        $data = $request->except(['exclusivity_data']);

        $requestedStatus = $request->input('status');

        unset($data['approved_by']);

        if ($requestedStatus === 'Disponible') {
            $data['status'] = 'Disponible';
            $data['approved_by'] = Auth::id();
        } else {
            unset($data['status']);
        }

        if (array_key_exists('exclusivity', $data)) {
            if (is_array($data['exclusivity'])) {
                $data['exclusivity'] = !empty($data['exclusivity']);
            } else {
                $data['exclusivity'] = filter_var($data['exclusivity'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $data['exclusivity'] = $data['exclusivity'] ?? (bool) $data['exclusivity'];
            }
        }

        $property->update($data);

        if ($request->has('exclusivity_data') && is_array($request->exclusivity_data)) {
            $exData = $request->exclusivity_data;
            $exData['property_id'] = $property->id;
            $exData['user_id'] = Auth::id();

            $existingExclusivity = $property->exclusivities()->latest()->first();

            if ($existingExclusivity) {
                $existingExclusivity->update($exData);
            } else {
                Exclusivity::create($exData);
            }
        }

        return created_responses('Transaction');
    }

    public function show($id)
    {
        $property = Property::with([
            'images',
            'documents',
            'exclusivities' => function ($query) {
                $query->latest();
            }
        ])->findOrFail($id);

        return response()->json($property);
    }

    /**
     * Return approved properties with coordinates for map display
     */
    public function getMapProperties()
    {
        $properties = Property::whereNotNull('map_lat')
            ->whereNotNull('map_lng')
            ->select('id', 'title', 'address', 'price', 'type', 'type_sale', 'status', 'map_lat', 'map_lng')
            ->latest('id')
            ->get();

        return response()->json($properties);
    }

    public function getMapTile($z, $x, $y)
    {
        try {
            $response = $this->mapHttpClient()->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");

            return response($response->getBody()->getContents(), 200, [
                'Content-Type' => $response->getHeaderLine('Content-Type') ?: 'image/png',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        } catch (GuzzleException $exception) {
            report($exception);

            return response()->json(['message' => 'No se pudieron cargar las calles del mapa.'], 502);
        }
    }

    public function uploadDocuments(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        $request->validate([
            'documents.*' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp|max:10240',
        ]);

        $saved = [];
        foreach ($request->file('documents', []) as $file) {
            $path = $file->store('property_documents', 'public');
            $document = PropertyDocument::create([
                'property_id' => $property->id,
                'name'        => $file->getClientOriginalName(),
                'path'        => $path,
                'mime_type'   => $file->getMimeType(),
            ]);
            $saved[] = $document;
        }

        return response()->json(['message' => 'Documentos subidos.', 'data' => $saved], 201);
    }

    public function deleteDocument(Request $request, $propertyId, $documentId)
    {
        $document = PropertyDocument::where('property_id', $propertyId)
            ->where('id', $documentId)
            ->firstOrFail();

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return response()->json(['message' => 'Documento eliminado.']);
    }

    public function export(Request $request)
    {
        $fileName = 'propiedades_' . now()->format('Y-m-d_H-i') . '.xlsx';
        return Excel::download(new PropertyExport($request), $fileName);
    }

    public function searchAddress(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        if (mb_strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            $response = $this->mapHttpClient()->get('https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'format' => 'json',
                    'q' => $query,
                    'countrycodes' => 've',
                    'limit' => 5,
                    'viewbox' => '-63.1,8.0,-62.3,8.6',
                    'bounded' => 1,
                ],
                'headers' => [
                    'Accept-Language' => 'es',
                ],
            ]);

            return response($response->getBody()->getContents(), 200, [
                'Content-Type' => $response->getHeaderLine('Content-Type') ?: 'application/json',
                'Cache-Control' => 'public, max-age=300',
            ]);
        } catch (GuzzleException $exception) {
            report($exception);

            return response()->json([], 502);
        }
    }

    private function mapHttpClient()
    {
        return new Client([
            'timeout' => 15,
            'connect_timeout' => 10,
            'http_errors' => true,
            'verify' => false,
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => config('app.name', 'Laravel') . '/1.0 map-proxy',
            ],
        ]);
    }
}

