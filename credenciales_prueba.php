<?php
/**
 * ============================================================
 * Archivo: credenciales_prueba.php
 * Propósito: Crear usuarios de prueba en la BD sin sobrescribir
 *            filas ni roles existentes (ver Aud: B9).
 * Uso:       http://localhost/bicicletas-compartidas/credenciales_prueba.php
 * ============================================================
 * NO inserta con id fijo (evita pisar Carlos Gómez/María Rodríguez).
 * NO crea ni renombra roles (los roles reales son ADMIN/OPER/MANT/CLI/SUP).
 * Para el esquema completo usar las migraciones 004-007.
 * ============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    // Contraseña de prueba (texto plano NO se almacena, solo el hash)
    $clavePlano = 'password123';
    $passwordHash = password_hash($clavePlano, PASSWORD_BCRYPT);

    $usuarios = [
        ['Andrés', 'López', 'admin@bicijardin.com', '312-456-7890', 'ADMIN'],
        ['María',  'Gómez', 'user@bicijardin.com',  '315-789-0123', 'CLI'],
    ];

    foreach ($usuarios as $u) {
        // rol por código, sin tocar el resto de filas de la tabla roles
        $idRol = $pdo->prepare("SELECT id_rol FROM roles WHERE codigo_rol = ?");
        $idRol->execute([$u[4]]);
        $rol = $idRol->fetchColumn();

        if ($rol === false) {
            throw new Exception("No existe el rol '" . $u[4] . "' en la BD. Ejecuta las migraciones 004-007.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, apellido, email, telefono, id_rol, password)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE password = VALUES(password)
        ");
        $stmt->execute([$u[0], $u[1], $u[2], $u[3], $rol, $passwordHash]);
    }

    $mensajeExito = "Usuarios de prueba sincronizados (solo se actualiza la contraseña, nunca los datos reales).";
} catch (Exception $e) {
    $error = "Error de Base de Datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credenciales de Prueba - BiciJardín</title>
    <style>
        :root {
            --bg-dark: #071710;
            --card-bg: #0d2318;
            --card-border: #183e2b;
            --text-30: #e2f1e8;
            --muted-30: #7a9e8b;
            --bg-60: #0a1c13;
            --accent-10: #22c55e;
            --accent-danger: #f43f5e;
            --accent-glow: #4ade80;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-30);
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 40px 20px;
        }

        .container { max-width: 700px; margin: 0 auto; }
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--card-border); font-size: 14px; }
        th { color: var(--muted-30); font-weight: 700; text-transform: uppercase; font-size: 11px; }

        .badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 12px; }
        .badge-admin { background: rgba(34, 197, 94, 0.2); color: var(--accent-glow); }
        .badge-user { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }

        code { background: var(--bg-60); padding: 4px 8px; border-radius: 4px; color: var(--accent-glow); font-family: monospace; }
        a { color: var(--accent-glow); text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1 style="font-size: 22px; margin: 0 0 8px 0;">🚲 Credenciales de Prueba - BiciJardín</h1>
            <p style="color: var(--muted-30); font-size: 13px; margin: 0;">
                Base de datos MySQL: <code>bicicletas_compartidas</code>
            </p>
            <?php if (isset($mensajeExito)): ?>
                <p style="color: var(--accent-glow); font-size: 13px; margin-top: 12px; font-weight: 600;">
                    ✓ <?php echo htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <p style="color: var(--accent-danger); font-size: 13px; margin-top: 12px; font-weight: 600;">
                    ✗ <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th>Contraseña</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge badge-admin">🔧 Administrador</span></td>
                        <td>Andrés López</td>
                        <td>admin@bicijardin.com</td>
                        <td><code>password123</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-user">🚴 Usuario</span></td>
                        <td>María Gómez</td>
                        <td>user@bicijardin.com</td>
                        <td><code>password123</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card" style="text-align: center;">
            <p style="color: var(--muted-30); font-size: 13px; margin: 0;">
                Ir al <a href="index.php?action=login">Formulario de Iniciar Sesión</a>
            </p>
        </div>
    </div>
</body>
</html>