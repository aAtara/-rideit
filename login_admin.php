<?php
include 'db.php';
include 'csrf.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Token de seguridad invalido.";
    } else {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $error = "Completa todos los campos.";
        } else {
            $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? AND role = 'admin'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = 'admin';
                header("Location: admin_panel.php");
                exit;
            } else {
                $error = "Credenciales invalidas o no eres administrador.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); }
        .glass { background: rgba(30, 27, 75, 0.7); backdrop-filter: blur(8px); border-radius: 1.25rem; border: 1px solid rgba(255,255,255,0.08); }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center text-gray-300">

    <div class="glass w-full max-w-sm mx-auto p-8 flex flex-col items-center shadow-2xl">
        <div class="mb-4 w-16 h-16 rounded-full flex items-center justify-center bg-gradient-to-r from-purple-600 to-indigo-600 shadow-lg text-3xl">
            🛡️
        </div>
        <h1 class="text-3xl font-extrabold text-white mb-2 text-center">Panel Admin</h1>
        <p class="text-indigo-300 mb-6 text-center text-sm">Acceso exclusivo para administradores</p>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login_admin.php" method="POST" class="w-full flex flex-col gap-4">
            <?php echo csrfField(); ?>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-200 mb-1">Correo</label>
                <input type="email" name="email" id="email" placeholder="admin@rideit.com" required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-200 mb-1">Contrasena</label>
                <input type="password" name="password" id="password" placeholder="Tu contrasena" required
                    class="w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <button type="submit"
                class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-lg mt-2">
                Iniciar Sesion
            </button>
        </form>

        <a href="index.html" class="mt-6 text-indigo-400 hover:underline text-sm">Volver al inicio</a>
    </div>

</body>
</html>
