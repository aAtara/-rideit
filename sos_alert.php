<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$tripId = isset($data['trip_id']) ? (int)$data['trip_id'] : 0;
$lat = isset($data['lat']) ? (float)$data['lat'] : 0;
$lng = isset($data['lng']) ? (float)$data['lng'] : 0;
$userId = $_SESSION['user_id'];

if ($tripId <= 0) {
    echo json_encode(["error" => "Datos invalidos"]);
    exit;
}

// Verificar que el viaje pertenece al usuario
$stmt = $conn->prepare("SELECT id FROM trips WHERE id = ? AND passenger_id = ?");
$stmt->bind_param("ii", $tripId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["error" => "Viaje no encontrado"]);
    exit;
}

// Registrar la alerta SOS (crear tabla si no existe)
$conn->query("CREATE TABLE IF NOT EXISTS sos_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    user_id INT NOT NULL,
    lat DOUBLE,
    lng DOUBLE,
    status VARCHAR(20) DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $conn->prepare("INSERT INTO sos_alerts (trip_id, user_id, lat, lng) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iidd", $tripId, $userId, $lat, $lng);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Alerta enviada"]);
} else {
    echo json_encode(["error" => "Error al registrar alerta"]);
}
?>
