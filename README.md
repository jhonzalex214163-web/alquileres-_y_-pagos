# BiciJardín - Sistema de Login con Arquitectura MVC

Aplicación web de movilidad sostenible para bicicletas compartidas en Jardín, Antioquia. Incluye sistema de autenticación por roles, panel de administración, monitoreo en tiempo real, conciliación financiera, y mapa interactivo de estaciones.

---

## Estructura del Proyecto

```
bicicletas-compartidas/
├── index.php                            # Router principal (front controller)
├── index2.php                           # Panel admin standalone con todas las vistas
├── usuario.php                          # Portal de usuario final
├── credenciales_prueba.php              # Script para sembrar BD con usuarios de prueba
├── verify_test.php                      # Verificador de hashes de contraseñas
├── config/
│   ├── config.php                       # Constantes globales + sesión
│   └── database.php                     # Conexión PDO singleton
├── controllers/
│   ├── AuthController.php               # Gestión de autenticación
│   ├── DashboardController.php          # Controlador del dashboard
│   ├── AlquilerController.php           # API de alquileres (iniciar/cerrar/monitorear)
│   └── PagoController.php               # API de pagos (registro, revocación, listado)
├── models/
│   ├── User.php                         # Modelo usuario (Active Record)
│   ├── Role.php                         # Modelo rol
│   ├── Alquiler.php                     # Modelo de alquileres (transacciones)
│   ├── Bicicleta.php                    # Modelo de bicicletas
│   └── MetodoPago.php                   # Modelo de métodos de pago (placeholder)
├── views/
│   ├── login.php                        # Formulario de acceso con credenciales de prueba
│   ├── dashboard.php                    # Dashboard completo con SPA integrada
│   └── viaje_en_curso.php               # Vista de viaje activo con monitoreo
├── js/
│   ├── app.js                           # SPA: tabs, mapa Leaflet, toggle día/noche
│   └── trip_monitor.js                  # Monitor de viaje en tiempo real
├── css/
│   └── styles.css                       # Estilos ecológicos oscuro/claro
├── database/
│   ├── script.sql                       # Schema completo de la base de datos
│   └── migrations/                      # Migraciones incrementales
│       ├── 000_add_password_to_usuarios.sql
│       ├── 001_create_alquileres_table.sql
│       ├── 002_create_metodos_pago_table.sql
│       └── 003_fidelizacion_mensual.sql
├── docs/
│   └── API_ALQUILERES.md                # Documentación de APIs y flujo de viaje
├── tests/
│   └── AlquilerTest.php                 # Pruebas del flujo de alquiler
├── img/
│   └── logo.png                         # Logo BiciJardín
└── .vscode/
    └── settings.json                    # Configuración del editor
```

---

## Detalle de Cada Archivo

### Punto de Entrada (Router)

#### `index.php`
Router principal y punto de entrada único. Lee `$_GET['action']` y despacha:

| `action` | Comportamiento |
|---|---|
| `login` | Si es POST: procesa credenciales vía `AuthController::login()`. Si es exitoso, redirige al dashboard; si falla, muestra error en `views/login.php`. Si es GET y el usuario ya está autenticado, redirige al dashboard. |
| `dashboard` | Llama a `requireLogin()` (bloquea acceso no autorizado) y carga `views/dashboard.php`. |
| `logout` | Cierra sesión y redirige a login. |

#### `index2.php`
Panel de administración standalone con control de acceso basado en `$_SESSION['usuario_rol']`. Presenta 5 secciones navegables: Fidelización, Auditoría, Monitoreo, Gestión de Viajes, y Métodos de Pago. El mapa y el toggle de tema están integrados.

#### `usuario.php`
Portal para usuarios regulares. Muestra la información personal, barra de progreso ecológica, y el mapa interactivo de estaciones. Acceso restringido a rol `user`.

### Configuración

#### `config/config.php`
Define constantes globales: credenciales de base de datos (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`), URL base (`BASE_URL`), y constantes de roles (`ROL_ADMIN`, `ROL_USER`). Inicia la sesión con `session_start()`.

#### `config/database.php`
Clase `Database` con patrón singleton. Usa PDO con `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, y `EMULATE_PREPARES=false`. El método `getConnection()` retorna una instancia única de PDO reutilizada en toda la aplicación.

### Controladores

#### `controllers/AuthController.php`
Gestión de autenticación de usuarios:

| Método | Descripción |
|---|---|
| `login(email, password)` | Busca el usuario por email, verifica el password con `password_verify()`, y almacena datos del usuario + rol en `$_SESSION`. |
| `logout()` | Vacía y destruye la sesión. |
| `isAuthenticated()` | Verifica si `$_SESSION['user_id']` está definido. |
| `requireLogin()` | Si no está autenticado, redirige a login; si lo está, retorna el objeto `User` desde la sesión. |
| `getUserFromSession()` | Recupera el usuario completo (con rol) desde la BD usando el email almacenado en sesión. |
| `getRol()` / `getRolNombre()` | Devuelven el código y nombre del rol desde la sesión. |

#### `controllers/DashboardController.php`
Controlador simple que requiere autenticación y carga `views/dashboard.php`. Actúa como guardia de acceso.

#### `controllers/AlquilerController.php`
API JSON para la gestión de alquileres de bicicletas. Responde a:

| Acción | Método | Descripción |
|---|---|---|
| `iniciar` | POST | Verifica disponibilidad de la bicicleta, crea el alquiler en estado `en_curso`, y actualiza el estado de la bicicleta a `Alquilada` (transacción atómica). |
| `cerrar` | POST | Valida la estación de destino, calcula minutos transcurridos y costo total usando la tarifa, finaliza el alquiler, libera la bicicleta a `Disponible`. |
| `monitorear` | GET | Devuelve telemetría en tiempo real: nivel de batería, estado, kilometraje, geolocalización, y alertas de mantenimiento. |

#### `controllers/PagoController.php`
Controlador de pagos con validación de seguridad HTTPS. Maneja:

| Acción | Descripción |
|---|---|
| `registrar_pago` | Registra transacción con referencia externa única, carga de comprobante fotográfico (upload), control de duplicados. |
| `revocar_token` | Revocación lógica de tokens de pasarela (`activo = 0`). |
| `listar_transacciones` | Lista todas las transacciones ordenadas por fecha. |

### Modelos

#### `models/User.php`
Modelo Active Record para usuarios. Propiedades públicas mapeadas a la tabla `usuarios`. Métodos:
- `findByEmail(email)` — Busca usuario + carga el rol asociado.
- `verificarPassword(passwordPlano)` — Usa `password_verify()`.
- `esAdmin()` / `esUsuario()` — Verifican el código de rol.

#### `models/Role.php`
Modelo para roles. Propiedades: `id_rol`, `codigo_rol`, `nombre_rol`, `nivel_acceso`. Método `findById(id_rol)` busca el rol en BD.

#### `models/Alquiler.php`
Modelo de alquileres. Implementa:
- `iniciarViaje()` — Usa `beginTransaction()` para atomicidad: crea el alquiler y cambia el estado de la bicicleta en una transacción.
- `cerrarViaje()` — Valida estación, calcula costo por minuto, actualiza alquiler y bicicleta.

#### `models/Bicicleta.php`
Modelo de bicicletas. Métodos:
- `actualizarEstado(id, estado)` — Cambia el estado (Disponible, Alquilada, etc.).
- `obtenerEstadoDetallado(id)` — Devuelve telemetría completa.

#### `models/MetodoPago.php`
Placeholder vacío para futuro desarrollo de métodos de pago.

### Vistas

#### `views/login.php`
Formulario de acceso con estilizado integrado (tema oscuro). Campos: `email` (type="email") y `password` (type="password"). Envía POST a `login.php?action=login`. Muestra credenciales de prueba al pie: `admin@bicijardin.com / password123` y `user@bicijardin.com / password123`.

#### `views/dashboard.php`
Dashboard principal con arquitectura SPA. Incluye:
- **Header**: Brand, usuario conectado, tabs de navegación (Dashboard, Fidelización, Auditoría), toggle día/noche, botón logout.
- **Vista Dashboard**: Información de rol y permisos (admin vs usuario).
- **Vista Fidelización**: Tarjetas de usuario, barra de progreso ecológica, select de usuario, **mapa Leaflet** con estaciones de Jardín.
- **Vista Auditoría**: Tabla de conciliación financiera y logs de auditoría.

#### `views/viaje_en_curso.php`
Vista de viaje activo. Muestra batería, estado del vehículo, alertas, y formulario de cierre de viaje con selector de estación de entrega.

### JavaScript

#### `js/app.js`
Núcleo de la SPA. Componentes:
- **Mock Data**: Base de datos simulada con usuarios, estaciones, conciliaciones y logs (reflejando la BD real).
- `toggleDayNight()` — Cambia entre modos claro/oscuro en `body` y persiste en `localStorage`.
- `cargarTemaPreferido()` — Carga el tema guardado en `localStorage` al iniciar.
- `switchTab(tab)` — Alterna entre vistas (dashboard, fidelizacion, auditoria). Inicializa el mapa lazy al mostrar la pestaña de fidelización.
- `cargarDatosUsuario(id)` — Renderiza tarjetas de usuario, barra de progreso y badge de beneficios.
- `initMap()` — Crea el mapa de Leaflet centrado en Jardín (5.5986, -75.8194) con 5 marcadores de estaciones.
- `refreshMap()` — Llama a `map.invalidateSize()` para corregir renderizado al cambiar de pestaña.
- `renderConciliacion()` — Renderiza tabla de conciliación y contenedor de logs.
- `ejecutarStoredProcedure()` — Simula la conciliación SQL.

#### `js/trip_monitor.js`
Monitoreo de viaje en tiempo real. Función `iniciarMonitoreo(idBicicleta)` que hace polling cada 5 segundos al endpoint de monitoreo, actualizando batería, estado y mostrando alertas si la batería está por debajo del 15%.

### CSS

#### `css/styles.css`
Sistema de diseño ecológico con paleta verde (regla 60-30-10): fondo oscuro `#091a13`, cards `#12281e`, acentos `#22c55e`. Incluye:
- **Tema oscuro** (por defecto): Variables `--bg-60`, `--text-30`, `--accent-10`, etc.
- **Tema claro** (`body.day-mode`): Override de variables para modo día.
- Toggle de tema con animación 🌙 ↔ ☀️ y transición de 0.3s.
- Filtro de inversión para mapa en modo oscuro (`brightness(0.7) invert(1)`).

### Base de Datas

#### `database/script.sql`
Schema completo exportado con HeidiSQL. Define todas las tablas:
`usuarios`, `roles`, `bicicletas`, `estaciones`, `alquileres`, `transacciones`, `tarifas`, `metodos_pago`, `mantenimientos`, `inventario_costos_arreglos`, `logs_auditoria`, `registro_energia`, `producto`, `factura`, `cliente`, `producto_factura`. Incluye claves foráneas, checks de validación y un procedimiento almacenado (`pc_lista_productos`).

#### `database/migrations/*.sql`
- `000_add_password_to_usuarios.sql` — Agrega columna `password` y crea roles predeterminados.
- `001_create_alquileres_table.sql` — Placeholder para tabla de alquileres.
- `002_create_metodos_pago_table.sql` — Placeholder para tabla de métodos de pago.
- `003_fidelizacion_mensual.sql` — Placeholder para lógica de fidelización mensual.

### Documentación

#### `docs/API_ALQUILERES.md`
Documenta el flujo completo del viaje: inicio (POST `iniciar`), monitoreo (GET `monitorear`), y cierre (POST `cerrar`), incluyendo payloads JSON y respuestas.

### Tests

#### `tests/AlquilerTest.php`
Prueba el flujo completo de alquiler: inicio de viaje → cierre de viaje → cálculo de costos. Para ejecutar: `php tests/AlquilerTest.php`.

### Tests de Verificación

#### `verify_test.php`
Verificador rápido de hashes de contraseñas usando `password_verify()`.

### Utilidades

#### `credenciales_prueba.php`
Script ejecutable que siembra la base de datos con roles y usuarios de prueba (admin y user, con password hash BCRYPT). También muestra una tabla con las credenciales y un enlace al formulario de login.

### Recursos

#### `img/logo.png`
Logo de BiciJardín (1MB, PNG).

#### `.vscode/settings.json`
Configuración del editor VS Code para el proyecto.

---

## Cómo Probar

```bash
# 1. Iniciar Apache + MySQL en Laragon
# 2. Crear base de datos y cargar el esquema
mysql -u root -e "CREATE DATABASE bicicletas_compartidas"
mysql -u root bicicletas_compartidas < database/script.sql
# 3. Ejecutar migración para crear password column y roles
mysql -u root bicicletas_compartidas < database/migrations/000_add_password_to_usuarios.sql
# 4. Sembrar usuarios de prueba
mysql -u root bicicletas_compartidas < database/migrations/000_add_password_to_usuarios.sql
# 5. Abrir en navegador: http://localhost/bicicletas-compartidas/index.php
#    Credenciales:
#      Admin: admin@bicijardin.com / password123
#      User:  user@bicijardin.com  / password123
```

## Rutas Principales

| URL | Descripción |
|---|---|
| `index.php?action=login` | Formulario de acceso |
| `index.php?action=dashboard` | Dashboard con SPA completa |
| `index.php?action=logout` | Cierra sesión |
| `index2.php` | Panel admin (requiere rol admin) |
| `usuario.php` | Portal usuario (requiere rol user) |
| `credenciales_prueba.php` | Siembra usuarios de prueba |

## APIs

| Endpoint | Acción | Descripción |
|---|---|---|
| `controllers/AlquilerController.php?action=iniciar` | POST | Inicia viaje de bicicleta |
| `controllers/AlquilerController.php?action=cerrar` | POST | Cierra viaje y calcula costo |
| `controllers/AlquilerController.php?action=monitorear&id_bicicleta=X` | GET | Telemetría en tiempo real |
| `controllers/PagoController.php?action=registrar_pago` | POST | Registro de pago con comprobante |
| `controllers/PagoController.php?action=revocar_token` | POST | Revocación lógica de token |
| `controllers/PagoController.php?action=listar_transacciones` | GET | Listado de transacciones |
