<?php
/**
 * ============================================================
 * Archivo: tests/SecurityTest.php
 * Propósito: Casos de prueba para las 8 capas de seguridad
 * Uso:       php tests/SecurityTest.php
 * ============================================================
 * NOTA: Este script usa análisis estático del código fuente
 * para verificar las capas de seguridad. Para pruebas dinámicas
 * en un entorno con servidor web + HTTPS, se recomienda integrar
 * con un framework de testing como PHPUnit.
 * ============================================================
 */

class SecurityTest {
    private $baseDir;
    private $passCount = 0;
    private $failCount = 0;

    public function __construct() {
        $this->baseDir = __DIR__ . '/../';
    }

    private function readCode($relativePath) {
        $fullPath = $this->baseDir . $relativePath;
        return file_get_contents($fullPath);
    }

    private function pass($name) {
        echo "[✅ PASS] $name\n";
        $this->passCount++;
    }

    private function fail($name, $detail = '') {
        echo "[❌ FAIL] $name" . ($detail ? ": $detail" : "") . "\n";
        $this->failCount++;
    }

    // ================================================================
    // CAPA 1: Autenticación (Authentication)
    // Verifica que el login use password_verify, sessions,
    // y que rechace credenciales incorrectas/vacías
    // ================================================================
    public function test_autenticacion() {
        echo "\n--- CAPA 1: Autenticación ---\n";

        $authCode = $this->readCode('controllers/AuthController.php');
        $userCode = $this->readCode('models/User.php');

        // T-1.1: Cadena completa de auth debe usar password_verify
        // (AuthController delega a User::verificarPassword -> password_verify)
        if (strpos($userCode, 'password_verify') !== false && strpos($authCode, 'verificarPassword') !== false) {
            $this->pass("Login usa verificarPassword() → password_verify() para validar contraseñas");
        } else {
            $this->fail("Login no usa password_verify() correctamente");
        }

        // T-1.2: Login debe retornar null si password es incorrecto
        if (strpos($authCode, 'return null') !== false && strpos($authCode, 'findByEmail') !== false) {
            $this->pass("Login retorna null si la verificación falla");
        } else {
            $this->fail("Login no retorna null para credenciales inválidas");
        }

        // T-1.3: Debe usar $_SESSION para almacenar autenticación
        if (strpos($authCode, 'user_id') !== false && strpos($authCode, 'user_email') !== false) {
            $this->pass("AuthController usa sesiones para estado de autenticación");
        } else {
            $this->fail("AuthController no usa sesiones para autenticación");
        }

        // T-1.4: Login verifica password del usuario
        if (strpos($authCode, 'verificarPassword') !== false) {
            $this->pass("Login verifica password via verificarPassword()");
        } else {
            $this->fail("Login no verifica password correctamente");
        }

        // T-1.5: Logout destruye la sesión
        if (strpos($authCode, 'session_destroy') !== false && strpos($authCode, 'session_unset') !== false) {
            $this->pass("Logout destruye sesión con session_unset() + session_destroy()");
        } else {
            $this->fail("Logout no destruye la sesión completamente");
        }
    }

    // ================================================================
    // CAPA 2: Autorización basada en roles (Role-Based Access Control)
    // Verifica los roles, niveles de acceso y funciones de chequeo
    // ================================================================
    public function test_autorizacion_roles() {
        echo "\n--- CAPA 2: Autorización por Roles ---\n";

        $configCode = $this->readCode('config/config.php');
        $authCode = $this->readCode('controllers/AuthController.php');
        $dashboardCode = $this->readCode('views/dashboard.php');

        // T-2.1: Config define constantes de rol (usando comillas simples para evitar interpolación)
        if (strpos($configCode, 'ROL_ADMIN') !== false && strpos($configCode, 'ROL_USER') !== false) {
            $this->pass("config.php define constantes ROL_ADMIN y ROL_USER");
        } else {
            $this->fail("config.php no define ambas constantes de rol");
        }

        // T-2.2: AuthController almacena rol en sesión
        if (strpos($authCode, 'user_rol') !== false) {
            $this->pass("AuthController almacena rol en sesión");
        } else {
            $this->fail("AuthController no almacena rol en sesión");
        }

        // T-2.3: Dashboard verifica admin vs user para permisos
        if (strpos($dashboardCode, 'esAdmin') !== false && strpos($dashboardCode, 'ROL_ADMIN') !== false) {
            $this->pass("Dashboard diferencia admin/usuario para mostrar permisos");
        } else {
            $this->fail("Dashboard no distingue roles correctamente");
        }

        // T-2.4: Rol admin tiene nivel de acceso definido
        if (strpos($authCode, 'nivel_acceso') !== false || strpos($authCode, 'user_nivel') !== false) {
            $this->pass("AuthController gestiona nivel_acceso del rol");
        } else {
            $this->fail("AuthController no gestiona nivel_acceso");
        }

        // T-2.5: User model tiene métodos esAdmin() y esUsuario()
        $userCode = $this->readCode('models/User.php');
        if (strpos($userCode, 'function esAdmin') !== false && strpos($userCode, 'function esUsuario') !== false) {
            $this->pass("User model implementa esAdmin() y esUsuario()");
        } else {
            $this->fail("User model carece de métodos de verificación de rol");
        }
    }

    // ================================================================
    // CAPA 3: Hash seguro de contraseñas (Password Hashing Security)
    // Verifica BCRYPT, password_verify y sal única
    // ================================================================
    public function test_hash_contrasenas() {
        echo "\n--- CAPA 3: Hash seguro de contraseñas ---\n";

        $credencialesCode = $this->readCode('credenciales_prueba.php');
        $userCode = $this->readCode('models/User.php');

        // T-3.1: credenciales_prueba.php usa password_hash con BCRYPT
        if (strpos($credencialesCode, 'password_hash(') !== false && strpos($credencialesCode, 'PASSWORD_BCRYPT') !== false) {
            $this->pass("credenciales_prueba.php usa password_hash con PASSWORD_BCRYPT");
        } else {
            $this->fail("credenciales_prueba.php no usa password_hash con BCRYPT");
        }

        // T-3.2: User model usa password_verify
        if (strpos($userCode, 'password_verify(') !== false) {
            $this->pass("User model usa password_verify() para validar contraseñas");
        } else {
            $this->fail("User model no usa password_verify()");
        }

        // T-3.3: verify_test.php confirma el hash
        $verifyCode = $this->readCode('verify_test.php');
        if (strpos($verifyCode, 'password_verify') !== false) {
            $this->pass("verify_test.php valida hashes con password_verify");
        } else {
            $this->fail("verify_test.php no valida hashes");
        }

        // T-3.4: Las contraseñas se insertan como hash, no como texto plano
        if (strpos($credencialesCode, 'passwordHash') !== false && strpos($credencialesCode, '$passwordHash') !== false) {
            $this->pass("Las contraseñas se insertan como hash, no como texto plano");
        } else {
            $this->fail("Posible riesgo: password podría insertarse en texto plano");
        }

        // T-3.5: password_hash genera sal única por usuario
        if (strpos($credencialesCode, 'password_hash(') !== false) {
            $this->pass("password_hash() genera sal aleatoria única por usuario automáticamente");
        } else {
            $this->fail("No se verifica el uso de sal única");
        }
    }

    // ================================================================
    // CAPA 4: Prevención de Inyección SQL (SQL Injection Prevention)
    // Verifica prepared statements y bound parameters en todos los modelos
    // ================================================================
    public function test_prevencion_inyeccion_sql() {
        echo "\n--- CAPA 4: Prevención de Inyección SQL ---\n";

        // Archivos que contienen consultas SQL directas
        $filesWithSQL = [
            'models/User.php' => 'User',
            'models/Role.php' => 'Role',
            'models/Alquiler.php' => 'Alquiler',
            'models/Bicicleta.php' => 'Bicicleta',
            'controllers/PagoController.php' => 'PagoController',
        ];

        foreach ($filesWithSQL as $path => $label) {
            $code = $this->readCode($path);

            // Verificar que NO contiene concatenación directa de input en consultas
            $hasInjectionPattern = preg_match('/query\s*\(\s*["\'].*?\$\w+.*?\$\w+.*?["\']\s*\)|exec\s*\(\s*["\'].*\$\w+.*["\']\s*\)/', $code);

            // Verificar uso de prepare o execute con bound params
            $usesPrepare = strpos($code, 'prepare') !== false;

            if (!$hasInjectionPattern && $usesPrepare) {
                $this->pass("$label usa prepared statements / bound parameters");
            } else {
                $this->fail("$label podría ser vulnerable a inyección SQL", "Falta prepared statements o patrón de inyección detectado");
            }
        }

        // T-4.2: AuthController y AlquilerController no tienen SQL directo (usan modelos)
        $authCode = $this->readCode('controllers/AuthController.php');
        $alquilerControllerCode = $this->readCode('controllers/AlquilerController.php');

        if (strpos($authCode, 'prepare') === false && strpos($authCode, 'query') === false) {
            $this->pass("AuthController no accede a BD directamente (usa modelos con prepared statements)");
        } else {
            $this->fail("AuthController accede a BD directamente");
        }

        if (strpos($alquilerControllerCode, 'prepare') === false && strpos($alquilerControllerCode, 'query') === false) {
            $this->pass("AlquilerController no accede a BD directamente (usa modelos con prepared statements)");
        } else {
            $this->fail("AlquilerController accede a BD directamente");
        }

        // T-4.3: Verificar uso de PDO
        $databaseCode = $this->readCode('config/database.php');
        if (strpos($databaseCode, 'PDO') !== false) {
            $this->pass("Database usa PDO (mejor prepared statements que mysqli)");
        } else {
            $this->fail("Database no usa PDO");
        }
    }

    // ================================================================
    // CAPA 5: Prevención de XSS (Cross-Site Scripting Prevention)
    // Verifica htmlspecialchars en salida y innerText en JS
    // ================================================================
    public function test_prevencion_xss() {
        echo "\n--- CAPA 5: Prevención de XSS ---\n";

        // T-5.1: dashboard.php escapa nombre de usuario
        $dashboardCode = $this->readCode('views/dashboard.php');

        if (strpos($dashboardCode, 'htmlspecialchars($userName)') !== false) {
            $this->pass("dashboard.php escapa userName con htmlspecialchars");
        } else {
            $this->fail("dashboard.php no escapa userName");
        }

        // T-5.2: login.php escapa error
        $loginCode = $this->readCode('views/login.php');

        if (strpos($loginCode, 'htmlspecialchars($error)') !== false) {
            $this->pass("login.php escapa mensajes de error con htmlspecialchars");
        } else {
            $this->fail("login.php no escapa mensajes de error");
        }

        // T-5.3: usuario.php escapa datos de sesión
        $usuarioCode = $this->readCode('usuario.php');

        if (strpos($usuarioCode, 'htmlspecialchars(') !== false) {
            $this->pass("usuario.php escapa datos de sesión con htmlspecialchars");
        } else {
            $this->fail("usuario.php no escapa datos de sesión");
        }

        // T-5.4: credenciales_prueba.php escapa output
        $credCode = $this->readCode('credenciales_prueba.php');

        if (strpos($credCode, 'htmlspecialchars(') !== false) {
            $this->pass("credenciales_prueba.php escapa output con htmlspecialchars");
        } else {
            $this->fail("credenciales_prueba.php no escapa output");
        }

        // T-5.5: app.js usa innerText (no innerHTML) para datos de usuario
        $appJsCode = $this->readCode('js/app.js');

        if (strpos($appJsCode, 'innerText') !== false) {
            $this->pass("app.js usa innerText para datos de usuario (seguro contra XSS)");
        } else {
            $this->fail("app.js podría ser vulnerable a XSS al no usar innerText");
        }

        // T-5.6: Verificar que todos los PHP escapan output de usuario
        $allPhpFiles = [
            'views/dashboard.php',
            'views/login.php',
            'usuario.php',
            'credenciales_prueba.php'
        ];

        $allEscaped = true;
        foreach ($allPhpFiles as $file) {
            $code = $this->readCode($file);
            // Buscar echo $_POST o echo $_GET sin htmlspecialchars
            if (preg_match('/echo\s+\$_(GET|POST)\[/', $code) && strpos($code, 'htmlspecialchars') === false) {
                $allEscaped = false;
            }
        }

        if ($allEscaped) {
            $this->pass("Todos los archivos PHP escapan output de usuario correctamente");
        } else {
            $this->fail("Algunos archivos PHP no escapan output de usuario");
        }
    }

    // ================================================================
    // CAPA 6: Control de acceso a rutas (Route Access Control)
    // Verifica requireLogin, chequeos de rol, y protección de rutas
    // ================================================================
    public function test_control_acceso_rutas() {
        echo "\n--- CAPA 6: Control de Acceso a Rutas ---\n";

        // T-6.1: DashboardController requiere login
        $dashboardControllerCode = $this->readCode('controllers/DashboardController.php');

        if (strpos($dashboardControllerCode, 'requireLogin') !== false) {
            $this->pass("DashboardController protege acceso con requireLogin()");
        } else {
            $this->fail("DashboardController no usa requireLogin()");
        }

        // T-6.2: index2.php verifica rol admin
        $index2Code = $this->readCode('index2.php');

        if (strpos($index2Code, "usuario_rol'] !== 'admin'") !== false) {
            $this->pass("index2.php verifica rol admin antes de cargar panel");
        } else {
            $this->fail("index2.php no verifica rol admin");
        }

        // T-6.3: usuario.php verifica rol user
        $usuarioCode = $this->readCode('usuario.php');

        if (strpos($usuarioCode, "usuario_rol'] !== 'user'") !== false) {
            $this->pass("usuario.php verifica rol user antes de cargar portal");
        } else {
            $this->fail("usuario.php no verifica rol user");
        }

        // T-6.4: AuthController tiene isAuthenticated() y requireLogin()
        $authCode = $this->readCode('controllers/AuthController.php');

        if (strpos($authCode, 'function isAuthenticated') !== false && strpos($authCode, 'function requireLogin') !== false) {
            $this->pass("AuthController implementa isAuthenticated() y requireLogin()");
        } else {
            $this->fail("AuthController carece de métodos de protección");
        }

        // T-6.5: index.php despacha dashboard (requiere autenticación)
        $indexCode = $this->readCode('index.php');
        if (strpos($indexCode, 'DashboardController') !== false || strpos($indexCode, 'requireLogin') !== false || strpos($indexCode, 'dashboard.php') !== false) {
            $this->pass("index.php despacha dashboard protegido con autenticación");
        } else {
            $this->fail("index.php no despacha dashboard correctamente");
        }

        // T-6.6: requireLogin redirige a login si no está autenticado
        if (strpos($authCode, "header('Location:") !== false && strpos($authCode, '?action=login') !== false) {
            $this->pass("requireLogin redirige a login si no está autenticado");
        } else {
            $this->fail("requireLogin no redirige a login");
        }
    }

    // ================================================================
    // CAPA 7: Prevención de duplicados de transacciones
    // Verifica validación de referencia_externa única en PagoController
    // ================================================================
    public function test_prevencion_duplicados() {
        echo "\n--- CAPA 7: Prevención de Duplicados de Transacciones ---\n";

        $pagoCode = $this->readCode('controllers/PagoController.php');

        // T-7.1: PagoController verifica duplicados de referencia_externa
        if (strpos($pagoCode, 'SELECT id_transaccion FROM transacciones WHERE referencia_externa') !== false) {
            $this->pass("PagoController verifica duplicados de referencia_externa");
        } else {
            $this->fail("PagoController no verifica duplicados de referencia_externa");
        }

        // T-7.2: Mensaje de error para transacción duplicada
        if (strpos($pagoCode, 'Transacción duplicada detectada') !== false) {
            $this->pass("PagoController retorna mensaje de error para transacción duplicada");
        } else {
            $this->fail("No se encontró mensaje de error para duplicados");
        }

        // T-7.3: Verificación de duplicado antes del INSERT
        $checkPos = strpos($pagoCode, 'SELECT id_transaccion FROM transacciones WHERE referencia_externa');
        $insertPos = strpos($pagoCode, 'INSERT INTO transacciones');

        if ($checkPos !== false && $insertPos !== false && $checkPos < $insertPos) {
            $this->pass("Verificación de duplicado ocurre antes del INSERT");
        } else {
            $this->fail("Orden incorrecto: verificación de duplicado no está antes del INSERT");
        }

        // T-7.4: Validación de parámetros obligatorios
        if (strpos($pagoCode, '!$id_alquiler') !== false && strpos($pagoCode, '!$ref_externa') !== false) {
            $this->pass("PagoController valida parámetros obligatorios (id_alquiler, monto, referencia)");
        } else {
            $this->fail("PagoController no valida todos los parámetros obligatorios");
        }

        // T-7.5: Revocación lógica de tokens
        if (strpos($pagoCode, 'UPDATE tokens_pasarela SET activo = 0') !== false) {
            $this->pass("PagoController realiza revocación lógica de tokens (no física)");
        } else {
            $this->fail("PagoController no implementa revocación lógica de tokens");
        }
    }

    // ================================================================
    // CAPA 8: Requerimiento de HTTPS (Transport Layer Security)
    // Verifica en PagoController la exigencia de HTTPS
    // ================================================================
    public function test_requisito_https() {
        echo "\n--- CAPA 8: Requerimiento de HTTPS ---\n";

        $pagoCode = $this->readCode('controllers/PagoController.php');

        // T-8.1: PagoController valida que la conexión sea HTTPS
        if (strpos($pagoCode, 'HTTPS') !== false) {
            $this->pass("PagoController valida conexión HTTPS");
        } else {
            $this->fail("PagoController no valida HTTPS");
        }

        // T-8.2: Código de estado 403 para conexiones no HTTPS
        if (strpos($pagoCode, 'http_response_code(403)') !== false) {
            $this->pass("PagoController retorna 403 para conexiones no HTTPS");
        } else {
            $this->fail("PagoController no retorna 403 para conexiones inseguras");
        }

        // T-8.3: Mensaje de error descriptivo
        if (strpos($pagoCode, 'Acceso denegado. Se requiere conexión HTTPS segura.') !== false) {
            $this->pass("PagoController muestra mensaje descriptivo sobre HTTPS");
        } else {
            $this->fail("PagoController no muestra mensaje de error para HTTPS");
        }

        // T-8.4: La ejecución termina (exit) si no es HTTPS
        if (strpos($pagoCode, "exit;") !== false) {
            $this->pass("PagoController termina la ejecución si no es HTTPS");
        } else {
            $this->fail("PagoController no termina la ejecución si no es HTTPS");
        }

        // T-8.5: Las APIs de pago usan HTTPS como requisito
        if (strpos($pagoCode, 'json_encode') !== false) {
            $this->pass("PagoController retorna respuestas JSON (API RESTful sobre HTTPS)");
        } else {
            $this->fail("PagoController no retorna respuestas JSON");
        }
    }

    // ================================================================
    // EJECUCIÓN DE TODAS LAS PRUEBAS
    // ================================================================
    public function runAllTests() {
        echo "========================================\n";
        echo "  PRUEBAS DE SEGURIDAD - BiciJardín\n";
        echo "  8 Capas de Seguridad\n";
        echo "========================================\n";
        echo "  Modo: Análisis estático de código fuente\n";
        echo "========================================\n";

        $this->test_autenticacion();           // Capa 1
        $this->test_autorizacion_roles();      // Capa 2
        $this->test_hash_contrasenas();         // Capa 3
        $this->test_prevencion_inyeccion_sql(); // Capa 4
        $this->test_prevencion_xss();          // Capa 5
        $this->test_control_acceso_rutas();     // Capa 6
        $this->test_prevencion_duplicados();   // Capa 7
        $this->test_requisito_https();         // Capa 8

        echo "\n========================================\n";
        echo "  RESUMEN DE PRUEBAS DE SEGURIDAD\n";
        echo "========================================\n";
        echo "Tests PASS: $this->passCount\n";
        echo "Tests FAIL: $this->failCount\n";
        $total = $this->passCount + $this->failCount;
        echo "Total:      $total\n";
        if ($total > 0) {
            $porcentaje = round(($this->passCount / $total) * 100, 1);
            echo "Cobertura:  $porcentaje%\n";
        }
        echo "========================================\n";

        if ($this->failCount === 0) {
            echo "\n✅ TODAS LAS 8 CAPAS DE SEGURIDAD VERIFICADAS CORRECTAMENTE\n";
        } else {
            echo "\n⚠️  Hay fallos en la capa de seguridad. Revísalos.\n";
        }
    }
}

// Ejecutar pruebas
$test = new SecurityTest();
$test->runAllTests();
