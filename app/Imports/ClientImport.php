<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Core\Auth\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ClientImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected int $imported = 0;
    protected array $errors = [];

    /**
     * The heading row is the first row; maatwebsite normalizes headers
     * (lowercase, spaces → underscores). Our Excel columns in uppercase map as:
     *
     *   FECHA              → fecha
     *   NOMBRE DEL CLIENTE → nombre_del_cliente
     *   UBICACION          → ubicacion
     *   TIPO DE INMUEBLE   → tipo_de_inmueble
     *   TIPO DE NEG        → tipo_de_neg
     *   TELEFONO           → telefono
     *   MEDIO              → medio
     *   AS. ASIGNADO       → as_asignado
     */
    public function collection(Collection $rows)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        foreach ($rows as $index => $row) {
            try {
                $rowNumber = $index + 2; // +2: index is 0-based, and row 1 is the heading row

                $name = trim((string) ($row['nombre_del_cliente'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $date = null;
                $rawDate = $row['fecha'] ?? null;
                if ($rawDate !== null && $rawDate !== '') {
                    try {
                        if (is_numeric($rawDate)) {
                            // Excel serial date
                            $date = Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)->format('Y-m-d'))->format('Y-m-d');
                        } else {
                            $date = Carbon::parse($rawDate)->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        $date = null;
                    }
                }

                $advisorId = null;
                $advisorName = trim((string) ($row['as_asignado'] ?? ''));
                if ($advisorName !== '') {
                    $user = User::where(function ($q) use ($advisorName) {
                        $q->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ["%{$advisorName}%"])
                          ->orWhereRaw("first_name LIKE ?", ["%{$advisorName}%"])
                          ->orWhereRaw("last_name LIKE ?", ["%{$advisorName}%"]);
                    })->first();
                    $advisorId = $user ? $user->id : null;
                }

                // Determine final advisor
                $assignedTo = $authUser->isAdmin()
                    ? $advisorId
                    : $authUser->id;

                Client::create([
                    'user_id'      => $authUser->id,
                    'name'         => $name,
                    'phone'        => trim((string) ($row['telefono'] ?? '')),
                    'date'         => $date,
                    'location'     => trim((string) ($row['ubicacion'] ?? '')),
                    'tipo_inmueble' => trim((string) ($row['tipo_de_inmueble'] ?? '')),
                    'tipo_neg'     => trim((string) ($row['tipo_de_neg'] ?? '')),
                    'source'       => $this->normalizeSource(trim((string) ($row['medio'] ?? ''))),
                    'assigned_to'  => $assignedTo,
                    'status'       => 'potencial',
                ]);

                $this->imported++;
            } catch (\Exception $e) {
                $this->errors[] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function normalizeSource(string $source): ?string
    {
        if ($source === '') {
            return null;
        }

        $map = [
            'telefono'    => 'telefono',
            'teléfono'    => 'telefono',
            'instagram'   => 'instagram',
            'tu inmueble' => 'tu_inmueble',
            'tu_inmueble' => 'tu_inmueble',
            'pendon'      => 'pendon',
            'pendón'      => 'pendon',
        ];

        return $map[mb_strtolower($source)] ?? $source;
    }
}
