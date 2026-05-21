<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido.']);
    exit;
}

if (!validateCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de seguridad invalido.']);
    exit;
}

$userId = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Debes ingresar tu contraseña para confirmar.']);
    exit;
}

// Verificar contraseña
$stmt = $conn->prepare("SELECT password, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
    exit;
}

// No permitir eliminar admins por esta via
if ($user['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Las cuentas de administrador no pueden eliminarse desde aqui.']);
    exit;
}

// Verificar que no tenga viajes activos (pendiente, asignado, en_destino, afuera)
$activeStatuses = ['pendiente', 'asignado', 'en_destino', 'afuera'];
$placeholders = implode(',', array_fill(0, count($activeStatuses), '?'));
$types = str_repeat('s', count($activeStatuses));

if ($user['role'] === 'conductor') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS activos FROM trips WHERE driver_id = ? AND status IN ($placeholders)");
    $params = array_merge([$userId], $activeStatuses);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS activos FROM trips WHERE passenger_id = ? AND status IN ($placeholders)");
    $params = array_merge([$userId], $activeStatuses);
}
$stmt->bind_param("i" . $types, ...$params);
$stmt->execute();
$active = $stmt->get_result()->fetch_assoc();

if ($active['activos'] > 0) {
    echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu cuenta mientras tengas viajes activos (' . $active['activos'] . ' viaje(s) en curso).']);
    exit;
}

// Desactivar chequeo de foreign keys temporalmente
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Eliminar preferencias del usuario
$stmt = $conn->prepare("DELETE FROM preferences WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// Eliminar alertas SOS del usuario
$conn->query("DELETE FROM sos_alerts WHERE user_id = $userId");

// Anonimizar viajes completados (poner driver_id a NULL)
$stmt = $conn->prepare("UPDATE trips SET driver_id = NULL WHERE driver_id = ? AND status IN ('completado', 'rechazado')");
$stmt->bind_param("i", $userId);
$stmt->execute();

// Eliminar viajes donde es pasajero (completados/rechazados)
$stmt = $conn->prepare("DELETE FROM trips WHERE passenger_id = ? AND status IN ('completado', 'rechazado')");
$stmt->bind_param("i", $userId);
$stmt->execute();

// Eliminar usuario
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    // Destruir sesion
    $_SESSION = [];
    session_destroy();
    if (isset($_COOKIE['rideit_remember'])) {
        setcookie('rideit_remember', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
    }
    // Reactivar foreign keys
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo json_encode(['success' => true, 'message' => 'Cuenta eliminada correctamente.', 'redirect' => $user['role'] === 'conductor' ? 'login_conductor.php' : 'login_pasajero.php']);
} else {
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la cuenta. Intenta de nuevo.']);
}
?>
