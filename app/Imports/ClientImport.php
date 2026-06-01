<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Core\Auth\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

                $advisorName = trim((string) ($row['as_asignado'] ?? ''));
                $advisorId = $this->resolveAdvisorId($advisorName);
                if ($advisorName !== '' && $advisorId === null) {
                    $this->errors[] = "Fila {$rowNumber}: no se encontro un asesor para '{$advisorName}'.";
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

    private function resolveAdvisorId(string $advisorName): ?int
    {
        $normalizedAdvisorName = $this->normalizeAdvisorName($advisorName);
        if ($normalizedAdvisorName === '') {
            return null;
        }

        $advisorTokens = $this->tokenizeAdvisorName($normalizedAdvisorName);
        $users = User::query()
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();

        foreach ($users as $user) {
            foreach ($this->advisorCandidatesForUser($user) as $candidate) {
                if ($candidate === $normalizedAdvisorName) {
                    return $user->id;
                }
            }
        }

        if (!empty($advisorTokens)) {
            $inputSignature = $this->normalizeTokenSignature($advisorTokens);

            foreach ($users as $user) {
                foreach ($this->advisorCandidatesForUser($user) as $candidate) {
                    $candidateTokens = $this->tokenizeAdvisorName($candidate);
                    if (!empty($candidateTokens) && $this->normalizeTokenSignature($candidateTokens) === $inputSignature) {
                        return $user->id;
                    }
                }
            }
        }

        $bestUserId = null;
        $bestScore = 0.0;

        foreach ($users as $user) {
            $score = $this->scoreAdvisorMatch($normalizedAdvisorName, $advisorTokens, $user);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestUserId = $user->id;
            }
        }

        return $bestScore >= 120.0 ? $bestUserId : null;
    }

    private function scoreAdvisorMatch(string $advisorName, array $advisorTokens, User $user): float
    {
        $bestScore = 0.0;

        foreach ($this->advisorCandidatesForUser($user) as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $candidateTokens = $this->tokenizeAdvisorName($candidate);
            $matchingTokens = array_intersect($advisorTokens, $candidateTokens);
            $score = 0.0;

            if (Str::contains($candidate, $advisorName) || Str::contains($advisorName, $candidate)) {
                $score += 90;
            }

            $score += count($matchingTokens) * 45;

            if (!empty($advisorTokens) && count($matchingTokens) === count($advisorTokens)) {
                $score += 80;
            }

            similar_text($advisorName, $candidate, $similarityPercentage);
            $score += $similarityPercentage;

            $distance = levenshtein($advisorName, $candidate);
            $score += max(0, 40 - ($distance * 3));

            $bestScore = max($bestScore, $score);
        }

        return $bestScore;
    }

    private function normalizeAdvisorName(string $value): string
    {
        return (string) Str::of(Str::ascii($value))
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]+/', ' ')
            ->squish();
    }

    private function tokenizeAdvisorName(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return collect(explode(' ', $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeTokenSignature(array $tokens): string
    {
        sort($tokens);

        return implode(' ', $tokens);
    }

    private function advisorCandidatesForUser(User $user): array
    {
        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName = trim((string) ($user->last_name ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);
        $reversedName = trim($lastName . ' ' . $firstName);
        $email = trim((string) ($user->email ?? ''));
        $emailLocalPart = $email !== '' ? Str::before($email, '@') : '';

        return collect([
            $this->normalizeAdvisorName($fullName),
            $this->normalizeAdvisorName($reversedName),
            $this->normalizeAdvisorName($firstName),
            $this->normalizeAdvisorName($lastName),
            $this->normalizeAdvisorName($email),
            $this->normalizeAdvisorName($emailLocalPart),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
