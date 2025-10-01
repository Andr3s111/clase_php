<?php
session_start();
date_default_timezone_set('America/Bogota');

// Función para conectar a la base de datos
function conectarBD() {
    $host = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "usuario_php";
    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);
    if ($conn->connect_error) {
        die("Error al conectar a la base de datos: " . $conn->connect_error);
    } 
    return $conn;
}

// Función para registrar el cierre de sesión
function registrarCierreSesion($session_id) {
    $conn = conectarBD();
    $fecha_cierre = date('Y-m-d H:i:s');
    $hora_cierre = date('H:i:s');
    
    $sql = "UPDATE sesiones_historial 
            SET fecha_cierre = ?, hora_cierre = ?
            WHERE session_id = ? AND fecha_cierre IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $fecha_cierre, $hora_cierre, $session_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Función para registrar inicio de sesión
function registrarInicioSesion($user_id, $session_id) {
    $conn = conectarBD();
    $fecha_inicio = date('Y-m-d H:i:s');
    $hora_inicio = date('H:i:s');
    
    $sql = "INSERT INTO sesiones_historial (user_id, session_id, fecha_inicio, hora_inicio) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $session_id, $fecha_inicio, $hora_inicio);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Manejo del logout
if (isset($_GET['logout'])) {
    // Registrar el cierre de sesión antes de destruir la sesión
    if (isset($_SESSION['user_id']) && isset($_SESSION['custom_session_id'])) {
        registrarCierreSesion($_SESSION['custom_session_id']);
    }
    session_destroy();
    header("Location: login.php");
    exit();
}

// Manejo del login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $conn = conectarBD();
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT id, username, password FROM login_user WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Generar un session_id personalizado (16 caracteres)
        $_SESSION['custom_session_id'] = bin2hex(random_bytes(8));
        
        // Registrar el inicio de sesión con el session_id personalizado
        registrarInicioSesion($user['id'], $_SESSION['custom_session_id']);
        
        header("Location: conexionBD_leer_registrar_eliminar_editar_css_sesion.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="estilo_nuevo.css">
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Iniciar Sesión</h2>
        <?php if (isset($error)): ?>
            <h3 style='color:red;'><?php echo $error; ?></h3>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-actions">
                <input type="submit" class="btn btn-primary" name="login" value="Ingresar">
            </div>
        </form>
    </div>
</body>
</html>