<?php

namespace App\Services;

use GuzzleHttp\Client;

class OpenAiDietService
{
    protected $http;
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->http = new Client(['base_uri' => 'https://api.openai.com/']);
        $this->apiKey = env('OPENAI_API_KEY');
        $this->model = 'gpt-4o-mini'; // Puedes usar gpt-4o o gpt-3.5-turbo si prefieres
    }

    /**
     * Genera una dieta personalizada usando la IA de OpenAI.
     */
    public function generateDietContent(array $userData): array
    {
        $prompt = $this->buildPrompt($userData);

        $response = $this->http->post('v1/chat/completions', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un nutricionista profesional. Devuelve un JSON estructurado con el plan.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => 1500,
            ],
        ]);

        $result = json_decode($response->getBody(), true);
        $text = $result['choices'][0]['message']['content'] ?? '';

        return $this->extractJson($text);
    }

    /**
     * Construye el prompt de la IA con las instrucciones.
     */
    protected function buildPrompt(array $data): string
    {
        $userJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        return <<<EOT
Genera un plan nutricional personalizado para el siguiente cliente. 
Devuelve SOLO un JSON válido con los campos:
{
  "title": string,
  "tmb_kcal": number,
  "total_kcal": number,
  "target_kcal": number,
  "macros": { "carbs": number, "protein": number, "fats": number },
  "ten_criteria": [string],
  "meal_options": {
      "breakfast": [string],
      "mid_morning": [string],
      "lunch": [string],
      "pre_post": [string],
      "dinner": [string]
  },
  "free_meal_rules": string,
  "supplements": string,
  "coach_notes": string,
  "footer": string
}

Calcula la TMB con la fórmula Mifflin-St Jeor y ajusta calorías según el objetivo:
- pérdida de grasa: -15%
- ganancia de masa: +10%
- mantenimiento: igual al gasto total.
Considera las preferencias y alergias. 
Cliente:
$userJson
EOT;
    }

    /**
     * Intenta extraer y decodificar el JSON del texto devuelto por la IA.
     */
    protected function extractJson(string $text): array
    {
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $match)) {
            $json = json_decode($match[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        return ['raw' => $text];
    }
}
