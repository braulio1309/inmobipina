<?php


namespace App\Helpers\Core\General;

class SanitizerHelper
{
    /**
     * @param $value
     * @return mixed
     */
    public function filterData($value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'filterData'], $value);
        }

        if (is_null($value) || is_bool($value)) {
            return $value;
        }

        $sanitizer = $this->generateSanitizer($value);

        if (!$sanitizer) {
            return $value;
        }

        return filter_var($value, $sanitizer);
    }

    /**
     * @param $data
     * @return false|int
     */
    public function generateSanitizer($data): bool|int
    {
        return match (gettype($data)) {
            'integer' => FILTER_SANITIZE_NUMBER_INT,
            'double' => FILTER_SANITIZE_NUMBER_FLOAT,
            'string' => FILTER_UNSAFE_RAW,
            default => false,
        };
    }
}
