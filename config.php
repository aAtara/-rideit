<?php
// Cargar .env si existe (desarrollo local)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Railway inyecta variables via getenv(), .env las pone en $_ENV
function env($key, $default = '') {
    return getenv($key) ?: ($_ENV[$key] ?? $default);
}

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'tuapp'));
define('DB_PORT', env('DB_PORT', '3306'));

define('DB_HOST_REMOTE', env('DB_HOST_REMOTE', ''));
define('DB_USER_REMOTE', env('DB_USER_REMOTE', ''));
define('DB_PASS_REMOTE', env('DB_PASS_REMOTE', ''));
define('DB_NAME_REMOTE', env('DB_NAME_REMOTE', ''));

define('GOOGLE_MAPS_API_KEY', env('GOOGLE_MAPS_API_KEY', ''));

// Configuracion de tarifas (pesos por km)
define('TARIFA_POR_KM', (float)env('TARIFA_POR_KM', '10'));
define('TARIFA_BASE', (float)env('TARIFA_BASE', '15'));
