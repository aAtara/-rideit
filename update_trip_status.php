<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$tripId = isset($data['trip_id']) ? (int)$data['trip_id'] : 0;
$status = $data['status'] ?? null;
$driverId = $_SESSION['user_id'];

$allowedStatuses = ['en_destino', 'completado', 'afuera'];

if ($tripId && $status && in_array($status, $allowedStatuses)) {
    $stmt = $conn->prepare("UPDATE trips SET status = ? WHERE id = ? AND driver_id = ?");
    $stmt->bind_param("sii", $status, $tripId, $driverId);

    if ($status === 'completado') {
        $stmt = $conn->prepare("UPDATE trips SET status = ?, completed_at = NOW() WHERE id = ? AND driver_id = ?");
        $stmt->bind_param("sii", $status, $tripId, $driverId);
    }

    $stmt->execute();
    echo json_encode(["success" => $stmt->affected_rows > 0]);
} else {
    echo json_encode(["error" => "Datos invalidos"]);
}
?>
