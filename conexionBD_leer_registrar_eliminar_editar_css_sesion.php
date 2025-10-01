<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRUD Usuarios</title>
    <link rel="stylesheet" href="estilo_nuevo.css">
</head>
<body>
<?php
/**
 * Funcion para iniciar la sesión y manejar el login de usuarios.
 */
session_start();

// Configurar zona horaria de Bogotá, Colombia
date_default_timezone_set('America/Bogota');

/**
 * Establece la conexión con la base de datos MySQL.
 * @return conn Objeto de conexión a la base de datos.
 */
function conectarBD() {
    $host = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "usuario_php";
    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);
    if ($conn->connect_error) {
        die("<h6 style='color:red;'>Error al conectar a la base de datos: " . $conn->connect_error . "</h6>");
    } 
    return $conn;
}

/**
 * Registra el inicio de sesión en el historial
 * @param int $user_id ID del usuario
 * @param string $session_id ID de la sesión PHP
 */
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

/**
 * Registra el cierre de sesión en el historial
 * @param string $session_id ID de la sesión PHP
 */
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

/**
 * Verifica si ya existe una sesión activa registrada para este session_id
 * @param string $session_id ID de la sesión PHP
 * @return bool True si ya existe, False si no
 */
function existeSesionActiva($session_id) {
    $conn = conectarBD();
    $sql = "SELECT id FROM sesiones_historial WHERE session_id = ? AND fecha_cierre IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    $conn->close();
    return $existe;
}

// Generar un session_id personalizado si no existe (16 caracteres)
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    // Si no existe un session_id personalizado, generarlo
    if (!isset($_SESSION['custom_session_id'])) {
        $_SESSION['custom_session_id'] = bin2hex(random_bytes(8)); // 8 bytes = 16 caracteres hex
    }
    
    $session_id_actual = $_SESSION['custom_session_id'];
    
    // Registrar inicio de sesión si no existe
    if (!existeSesionActiva($session_id_actual)) {
        registrarInicioSesion($_SESSION['user_id'], $session_id_actual);
    }
}

/**
 * Realiza una consulta SQL para obtener los usuarios registrados.
 * @return result Resultado de la consulta SQL.
 * @name consultarBD
 */
function consultarBD() {
    $conn = conectarBD();
    // Consulta para obtener los usuarios registrados
    $sql = "SELECT id, username AS username, password, created_at, updated_at FROM login_user";
    $result = $conn->query($sql);
    // retorna el resultado de la consulta
    if (!$result) {
        die("Error en la consulta: " . $conn->error);
    }
    return $result;
}
$result = consultarBD();

//Sirve para cargar los datos de un usuario específico 
//cuando presionas el botón "Editar" en la tabla.
$editar_usuario = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_id'])) {
    $conn = conectarBD();
    $id = intval($_POST['editar_id']);
    $sql = "SELECT * FROM login_user WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editar_usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

?>
<!-- Formulario de registro de usuarios -->
<div class="form-container">
    <h2 class="form-title"><?php echo $editar_usuario ? "Editar Usuario" : "Formulario de Registro"; ?></h2>
        <form action="conexionBD_leer_registrar_eliminar_editar_css_sesion.php" method="post">
            <div style="text-align: right; margin: 10px 5%;">
                <?php
                echo "User Id: " . $_SESSION['user_id'];
                $nombreSession = session_name();
                $idSession = isset($_SESSION['custom_session_id']) ? $_SESSION['custom_session_id'] : session_id();
                echo " | Session Name: " . $nombreSession . " | Session Id: " . $idSession . " |";
                ?>
               Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?> | 
                <a href="login.php?logout">Cerrar sesión</a>
            </div>
            <?php if ($editar_usuario): ?>
                <input type="hidden" name="id" value="<?php echo $editar_usuario['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="user">Usuario</label>
                <input type="text" name="user" placeholder="Usuario" value="<?php echo $editar_usuario ? htmlspecialchars($editar_usuario['username']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" placeholder="Contraseña" value="">
            </div>
            <div class="form-actions">
                <input type="submit" class="btn btn-primary" name="<?php echo $editar_usuario ? 'actualizar' : 'registrar'; ?>" value="<?php echo $editar_usuario ? 'Actualizar' : 'Registrar'; ?>">
                <?php if ($editar_usuario): ?>
                    <a href="conexionBD_leer_registrar_eliminar_editar_css_sesion.php" class="cancel-btn">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
</div>

<?php

$conn = conectarBD();
// Guardar en base de datos.

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar']) && isset($_POST['user']) && isset($_POST['password'])) {
    $user = $_POST["user"];
    $password = $_POST["password"];

    // Verificar si el usuario ya existe
    $sql_check = "SELECT id FROM login_user WHERE username = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $user);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo "<h3 style='color:red;'>El nombre de usuario ya existe. Por favor elige otro.</h3>";
    } else {
        $sql = "INSERT INTO login_user (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $user, $password);
        if ($stmt->execute()) {
            echo "<h3 style='color:green;'>Usuario registrado correctamente.</h3>";
        } else {
            echo "Error al registrar usuario: " . $conn->error;
        }
        $stmt->close();
    }
    $stmt_check->close();
    $result = consultarBD();
}

// Actualizar usuario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar']) && isset($_POST['id'])) {
    $conn = conectarBD();
    $id = intval($_POST['id']);
    $user = $_POST["user"];
    $password = $_POST["password"];

    // Verificar si el usuario ya existe en otro registro
    $sql_check = "SELECT id FROM login_user WHERE username = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $user, $id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo "<h3 style='color:red;'>El nombre de usuario ya existe en otro registro. Por favor elige otro.</h3>";
    } else {
        if (!empty($password)) {
            $sql = "UPDATE login_user SET username=?, password=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $user, $password, $id);
        } else {
            $sql = "UPDATE login_user SET username=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $user, $id);
        }
        if ($stmt->execute()) {
            echo "<h3 style='color:green;'>Usuario actualizado correctamente.</h3>";
        } else {
            echo "Error al actualizar usuario: " . $conn->error;
        }
        $stmt->close();
    }
    $stmt_check->close();
    $result = consultarBD();
}

/**
 * Funcion para eliminar un usuario por ID.
 * @param int $id El ID del usuario a eliminar.
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_id'])) {
    $conn = conectarBD();
    $id = intval($_POST['eliminar_id']);// convierte el ID a un entero para evitar inyecciones SQL
    $sql = "DELETE FROM login_user WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<h3 style='color:red;'>Usuario eliminado correctamente.</h3>";
    } else {
        echo "<h3 style='color:red;'>Error al eliminar usuario: " . $conn->error . "</h3>";
    }
    $stmt->close();
}
$result = consultarBD();

?>

<!-- Tabla donde se presenta la consulta a la base datos -->
<table border="1" cellpadding="5" cellspacing="0" style="margin-top:30px; width:100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Contraseña</th>
            <th>Creado</th>
            <th>Actualizado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['password']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                    <td>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Eliminar Este usuario?');">
                            <input type="hidden" name="eliminar_id" value="<?php echo $row['id']; ?>">
                            <input type="submit" value="Eliminar">
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="editar_id" value="<?php echo $row['id']; ?>">
                            <input type="submit" value="Editar">
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No hay usuarios registrados</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Tabla de Historial de Sesiones -->
<h2 style="margin-top: 40px; text-align: center;">Historial de Sesiones</h2>
<table border="1" cellpadding="5" cellspacing="0" style="margin-top:20px; width:100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Session ID</th>
            <th>Fecha Inicio</th>
            <th>Hora Inicio</th>
            <th>Fecha Cierre</th>
            <th>Hora Cierre</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $conn = conectarBD();
        $sql = "SELECT sh.user_id, sh.session_id, sh.fecha_inicio, sh.hora_inicio, 
                       sh.fecha_cierre, sh.hora_cierre, lu.username 
                FROM sesiones_historial sh 
                INNER JOIN login_user lu ON sh.user_id = lu.id 
                ORDER BY sh.fecha_inicio DESC 
                LIMIT 50";
        $result_sesiones = $conn->query($sql);
        
        if ($result_sesiones && $result_sesiones->num_rows > 0):
            while($row = $result_sesiones->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td style="font-size: 12px;"><?php echo htmlspecialchars($row['session_id']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($row['fecha_inicio'])); ?></td>
                    <td><?php echo htmlspecialchars($row['hora_inicio']); ?></td>
                    <td><?php echo $row['fecha_cierre'] ? date('Y-m-d', strtotime($row['fecha_cierre'])) : '<em>-</em>'; ?></td>
                    <td><?php echo $row['hora_cierre'] ? htmlspecialchars($row['hora_cierre']) : '<em>-</em>'; ?></td>
                </tr>
            <?php endwhile;
        else: ?>
            <tr><td colspan="7" style="text-align:center;">No hay sesiones registradas</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>