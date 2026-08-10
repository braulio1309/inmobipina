<?php

namespace Tests\Feature;

use App\Http\Controllers\PropertyController;
use ReflectionMethod;
use Tests\TestCase;

class PropertyCaptationTest extends TestCase
{
    public function test_normalize_captation_data_includes_other_real_estate_fields()
    {
        $controller = new PropertyController();
        $method = new ReflectionMethod($controller, 'normalizeCaptationData');
        $method->setAccessible(true);

        $normalized = $method->invoke($controller, [
            'fecha_captacion' => '2026-08-10',
            'es_otra_inmobiliaria' => '1',
            'nombre_inmobiliaria' => 'Inmobiliaria Ejemplo',
            'numero_contacto' => '0414-1234567',
        ]);

        $this->assertTrue($normalized['es_otra_inmobiliaria']);
        $this->assertSame('Inmobiliaria Ejemplo', $normalized['nombre_inmobiliaria']);
        $this->assertSame('0414-1234567', $normalized['numero_contacto']);
    }
}
