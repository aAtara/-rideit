<?php
include 'db.php';
include 'csrf.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        header("Location: login.html?error=invalid_token");
        exit;
    }

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        header("Location: login.html?error=empty_fields");
        exit;
    }

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    if (!$stmt) {
        die("Error en la preparacion de la consulta.");
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            header("Location: dashboard.php");
            exit;
        } else {
            header("Location: login.html?error=invalid_password");
            exit;
        }
    } else {
        header("Location: login.html?error=user_not_found");
        exit;
    }
} else {
    header("Location: login.html");
    exit;
}
?>
