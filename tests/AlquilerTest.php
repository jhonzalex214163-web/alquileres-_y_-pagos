<?php
// tests/AlquilerTest.php
require_once __DIR__ . '/../models/Alquiler.php';

class AlquilerTest {
    public function testFlujoCompletoAlquiler() {
        $alquiler = new Alquiler();
        
        echo "=== Ejecutando Prueba: Inicio de Viaje ===\n";
        // Asumiendo usuario=1, bicicleta=1, estacion=1
        $idAlquiler = $alquiler->iniciarViaje(1, 1, 1);
        assert($idAlquiler > 0, "Error: El viaje no se inició correctamente.");
        echo "✅ Viaje iniciado con ID: $idAlquiler\n";

        echo "=== Ejecutando Prueba: Cierre de Viaje ===\n";
        $resultado = $alquiler->cerrarViaje($idAlquiler, 2);
        assert($resultado['costo_total'] >= 0, "Error: El cálculo del costo falló.");
        echo "✅ Viaje finalizado correctamente. Costo: $" . $resultado['costo_total'] . "\n";
    }
}

// Para ejecutar: php tests/AlquilerTest.php
$test = new AlquilerTest();
$test->testFlujoCompletoAlquiler();