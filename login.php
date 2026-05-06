<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validar campos vacíos
    if (empty($email) || empty($password)) {
        header("Location: login.html?error=empty_fields");
        exit;
    }

    // Consultar la base de datos
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Validar la contraseña utilizando password_verify
        if (password_verify($password, $user['password'])) {
            // Establecer las variables de sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            // Redirigir al dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            // Contraseña incorrecta
            header("Location: login.html?error=invalid_password");
            exit;
        }
    } else {
        // Usuario no encontrado
        header("Location: login.html?error=user_not_found");
        exit;
    }
} else {
    // Si no se envió un formulario POST
    header("Location: login.html");
    exit;
}
?>
