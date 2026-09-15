<?php
require_once __DIR__ . '/../config/config.php';

$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiciJardín - Iniciar Sesión</title>
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
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
        }

        .card-login {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }

        .input-field {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--card-border);
            border-radius: 10px;
            background-color: var(--bg-60);
            color: var(--text-30);
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .input-field:focus {
            border-color: var(--accent-10);
        }

        .btn-submit {
            background-color: var(--accent-10);
            color: #052e16;
            font-weight: 800;
            cursor: pointer;
            border: none;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            transition: opacity 0.2s;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div class="card-login" style="max-width: 380px; width: 100%;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="font-size: 24px; font-weight: 800; margin-top: 12px; color: var(--text-30); display: flex; align-items: center; justify-content: center; gap: 8px;">
                    🚲 BiciJardín
                </h2>
                <p style="font-size: 13px; margin-top: 4px; color: var(--muted-30);">
                    Ingresa tus credenciales para continuar
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: rgba(244, 63, 94, 0.1); border: 1px solid var(--accent-danger); border-radius: 8px; padding: 10px; margin-bottom: 16px;">
                    <p style="color: var(--accent-danger); font-size: 13px; font-weight: 600; margin: 0; text-align: center;">
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=login" style="display: flex; flex-direction: column; gap: 16px;">
                <?php echo csrf_field(); ?>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: var(--muted-30); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        Correo Electrónico
                    </label>
                    <input type="email" name="email" required placeholder="tucorreo@bicijardin.com" class="input-field">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: var(--muted-30); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        Contraseña
                    </label>
                    <input type="password" name="password" required placeholder="••••••••" class="input-field">
                </div>
                <button type="submit" class="btn-submit" style="margin-top: 8px;">
                    Iniciar Sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>