<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token de seguridad invalido.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $type = trim($_POST['type']);
    $address = trim($_POST['address']);

    if (empty($type) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO preferences (user_id, type, address) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $type, $address);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Preferencia agregada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar la preferencia.']);
    }
    exit;
}
?>
