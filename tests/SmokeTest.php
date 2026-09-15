<?php
/**
 * ============================================================
 * tests/SmokeTest.php — Pruebas de humo DINÁMICAS (flujo real)
 * Uso:   php tests/SmokeTest.php
 * Banco: C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe tests\SmokeTest.php
 * ============================================================
 * Verifica contra la BD real que login, alquiler y pago funcionan
 * (no es análisis estático). Requiere migraciones 004-007 aplicadas.
 * El test de alquiler y pago limpia los registros de prueba creados.
 * ============================================================
 */

error_reporting(E_ALL & ~E_DEPRECATED);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Alquiler.php';

class SmokeTest {
    private int $pass = 0;
    private int $fail = 0;

    private function check(bool $ok, string $name, string $detail = ''): void
    {
        if ($ok) {
            echo "[PASS] $name\n";
            $this->pass++;
        } else {
            echo "[FAIL] $name" . ($detail ? ": $detail" : "") . "\n";
            $this->fail++;
        }
    }

    public function testLogin(): void
    {
        echo "\n--- Login (AuthController, BD real) ---\n";

        // El CLI de PHP considera la salida previa como "headers enviados".
        // Se bufferiza para que session_regenerate_id() no emita warnings.
        $login = function (string $email, string $pass) {
            ob_start();
            $user = AuthController::login($email, $pass);
            $rol = $_SESSION['usuario_rol'] ?? null;
            ob_end_clean();
            return [$user, $rol];
        };
        $logout = function () {
            ob_start();
            AuthController::logout();
            ob_end_clean();
        };

        [$admin, $rolAdmin] = $login('admin@bicijardin.com', 'password123');
        $this->check($admin !== null, 'Login admin@bicijardin.com con password123');
        $this->check($admin !== null && $admin->esAdmin(), 'El usuario admin tiene rol ADMIN', $admin ? 'rol=' . ($admin->rol->codigo_rol ?? 'null') : 'no autenticado');
        $this->check($rolAdmin === 'admin', 'Sesión expone clave usuario_rol=admin (index2.php)', (string)$rolAdmin);
        $logout();

        [$user, $rolUser] = $login('user@bicijardin.com', 'password123');
        $this->check($user !== null, 'Login user@bicijardin.com con password123');
        $this->check($rolUser === 'cli', 'Sesión expone clave usuario_rol=cli (usuario.php)', (string)$rolUser);
        $logout();

        [$bad] = $login('admin@bicijardin.com', 'clave-incorrecta');
        $this->check($bad === null, 'Password incorrecto es rechazado');
    }

    public function testFlujoAlquiler(): void
    {
        echo "\n--- Alquiler (iniciar + cerrar, BD real) ---\n";

        $pdo = Database::getConnection();

        // Elegir una bicicleta 'disponible' y un usuario de prueba nuevo
        $usuarioNuevo = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = 'user@bicijardin.com'");
        $usuarioNuevo->execute();
        $idUsuario = (int)$usuarioNuevo->fetchColumn();

        $bici = $pdo->query("SELECT id_bicicleta FROM bicicletas WHERE estado = 'disponible' ORDER BY id_bicicleta LIMIT 1")->fetchColumn();

        $alquiler = new Alquiler();
        try {
            $idAlquiler = $alquiler->iniciarViaje($idUsuario, (int)$bici, 1);
            $this->check($idAlquiler > 0, "iniciarViaje crea alquiler id=$idAlquiler");

            $biciState = $pdo->prepare("SELECT estado FROM bicicletas WHERE id_bicicleta = ?");
            $biciState->execute([$bici]);
            $this->check(strtolower((string)$biciState->fetchColumn()) === 'alquilada', 'La bicicleta queda en estado alquilada');

            $res = $alquiler->cerrarViaje($idAlquiler, 2);
            $this->check($res['costo_total'] >= 0, 'cerrarViaje calcula costo', "costo=" . $res['costo_total']);

            $biciState->execute([$bici]);
            $this->check(strtolower((string)$biciState->fetchColumn()) === 'disponible', 'La bicicleta vuelve a disponible');

            // Limpieza del registro de prueba
            $pdo->prepare("DELETE FROM alquileres WHERE id_alquiler = ?")->execute([$idAlquiler]);
        } catch (Throwable $e) {
            $this->check(false, 'Flujo de alquiler', $e->getMessage());
        }
    }

    public function testPago(): void
    {
        echo "\n--- Pago (INSERT transacciones con referencia única) ---\n";

        $pdo = Database::getConnection();

        $alq = $pdo->query("SELECT id_alquiler FROM alquileres ORDER BY id_alquiler DESC LIMIT 1")->fetchColumn();
        $ref = 'SMOKE_' . bin2hex(random_bytes(4));
        $monto = 12.5;

        $check = $pdo->prepare("SELECT id_transaccion FROM transacciones WHERE referencia_externa = ?");
        $check->execute([$ref]);
        $this->check(!$check->fetch(), 'Referencia no duplicada antes de insertar');

        $stmt = $pdo->prepare(
            "INSERT INTO transacciones (id_alquiler, id_metodo, referencia_externa, monto, estado, comprobante_url, valor, fecha_pago)
             VALUES (?, NULL, ?, ?, 'EXITOSO', NULL, ?, NOW())"
        );
        $ok = $stmt->execute([$alq, $ref, $monto, $monto]);
        $this->check($ok, 'INSERT transacción con columnas migradas (referencia_externa/monto/estado)');
        $idTx = (int)$pdo->lastInsertId();

        // Duplicidad: la misma referencia debe detectarse
        $check->execute([$ref]);
        $this->check((bool)$check->fetch(), 'Control de duplicidad detecta la misma referencia');

        // Limpieza
        $pdo->prepare("DELETE FROM transacciones WHERE id_transaccion = ?")->execute([$idTx]);
    }

    public function runAll(): void
    {
        echo "==============================\n";
        echo "  SMOKE TESTS — BiciJardín\n";
        echo "==============================\n";

        $this->testLogin();
        $this->testFlujoAlquiler();
        $this->testPago();

        echo "\n==============================\n";
        echo "PASS: {$this->pass}\n";
        echo "FAIL: {$this->fail}\n";
        echo "Total: " . ($this->pass + $this->fail) . "\n";
        echo "==============================\n";
        exit($this->fail > 0 ? 1 : 0);
    }
}

$smoke = new SmokeTest();
$smoke->runAll();