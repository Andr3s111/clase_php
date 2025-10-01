<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Productos</title>
    <link rel="stylesheet" href="estilo_nuevo.css">
    <style>
        .producto-imagen {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .sin-imagen {
            width: 80px;
            height: 80px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/**
 * Establece la conexión con la base de datos MySQL.
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
 * Consulta todos los productos de la base de datos
 */
function consultarProductos() {
    $conn = conectarBD();
    $sql = "SELECT id, nombre, descripcion, precio, cantidad, imagen FROM productos ORDER BY id DESC";
    $result = $conn->query($sql);
    if (!$result) {
        die("Error en la consulta: " . $conn->error);
    }
    return $result;
}
$result = consultarProductos();

// Cargar datos de un producto para editar
$editar_producto = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_id'])) {
    $conn = conectarBD();
    $id = intval($_POST['editar_id']);
    $sql = "SELECT * FROM productos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editar_producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

?>

<!-- Formulario de productos -->
<div class="form-container">
    <h2 class="form-title"><?php echo $editar_producto ? "Editar Producto" : "Registrar Producto"; ?></h2>
    <form action="productos.php" method="post" enctype="multipart/form-data">
        <div style="text-align: right; margin: 10px 5%;">
            Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?> | 
            <a href="login.php?logout">Cerrar sesión</a>
        </div>
        
        <?php if ($editar_producto): ?>
            <input type="hidden" name="id" value="<?php echo $editar_producto['id']; ?>">
            <?php if ($editar_producto['imagen']): ?>
                <div style="margin-bottom: 15px;">
                    <label>Imagen actual:</label><br>
                    <img src="<?php echo htmlspecialchars($editar_producto['imagen']); ?>" class="producto-imagen" alt="Imagen actual">
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="nombre">Nombre del Producto *</label>
            <input type="text" name="nombre" placeholder="Ej: Manzana Roja" required value="<?php echo $editar_producto ? htmlspecialchars($editar_producto['nombre']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea name="descripcion" placeholder="Descripción del producto" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><?php echo $editar_producto ? htmlspecialchars($editar_producto['descripcion']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="precio">Precio (COP) *</label>
            <input type="number" name="precio" step="0.01" placeholder="Ej: 2500.00" required value="<?php echo $editar_producto ? $editar_producto['precio'] : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="cantidad">Cantidad *</label>
            <input type="number" name="cantidad" placeholder="Ej: 50" required value="<?php echo $editar_producto ? $editar_producto['cantidad'] : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="imagen">Imagen del Producto <?php echo $editar_producto ? '(dejar vacío para mantener la actual)' : ''; ?></label>
            <input type="file" name="imagen" accept="image/*" style="padding: 5px;">
        </div>
        
        <div class="form-actions">
            <input type="submit" class="btn btn-primary" name="<?php echo $editar_producto ? 'actualizar' : 'registrar'; ?>" value="<?php echo $editar_producto ? 'Actualizar' : 'Registrar'; ?>">
            <?php if ($editar_producto): ?>
                <a href="productos.php" class="cancel-btn">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php
$conn = conectarBD();

// REGISTRAR PRODUCTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $precio = floatval($_POST["precio"]);
    $cantidad = intval($_POST["cantidad"]);
    $imagen_ruta = null;

    // Manejo de la imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $directorio_destino = "uploads/";
        
        // Crear la carpeta si no existe
        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }
        
        $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta_completa = $directorio_destino . $nombre_archivo;
        
        // Validar tipo de archivo
        $tipo_permitido = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
        if (in_array($_FILES['imagen']['type'], $tipo_permitido)) {
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
                $imagen_ruta = $ruta_completa;
            } else {
                echo "<h3 style='color:red;'>Error al subir la imagen.</h3>";
            }
        } else {
            echo "<h3 style='color:red;'>Solo se permiten imágenes JPG, JPEG, PNG o GIF.</h3>";
        }
    }

    $sql = "INSERT INTO productos (nombre, descripcion, precio, cantidad, imagen) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdis", $nombre, $descripcion, $precio, $cantidad, $imagen_ruta);
    
    if ($stmt->execute()) {
        echo "<h3 style='color:green;'>Producto registrado correctamente.</h3>";
    } else {
        echo "<h3 style='color:red;'>Error al registrar producto: " . $conn->error . "</h3>";
    }
    $stmt->close();
    $result = consultarProductos();
}

// ACTUALIZAR PRODUCTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $precio = floatval($_POST["precio"]);
    $cantidad = intval($_POST["cantidad"]);
    
    // Obtener la imagen actual
    $sql_img = "SELECT imagen FROM productos WHERE id = ?";
    $stmt_img = $conn->prepare($sql_img);
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    $producto_actual = $result_img->fetch_assoc();
    $imagen_ruta = $producto_actual['imagen'];
    $stmt_img->close();
    
    // Manejo de nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $directorio_destino = "uploads/";
        $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta_completa = $directorio_destino . $nombre_archivo;
        
        $tipo_permitido = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
        if (in_array($_FILES['imagen']['type'], $tipo_permitido)) {
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
                // Eliminar imagen anterior si existe
                if ($imagen_ruta && file_exists($imagen_ruta)) {
                    unlink($imagen_ruta);
                }
                $imagen_ruta = $ruta_completa;
            }
        }
    }
    
    $sql = "UPDATE productos SET nombre=?, descripcion=?, precio=?, cantidad=?, imagen=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdisi", $nombre, $descripcion, $precio, $cantidad, $imagen_ruta, $id);
    
    if ($stmt->execute()) {
        echo "<h3 style='color:green;'>Producto actualizado correctamente.</h3>";
    } else {
        echo "<h3 style='color:red;'>Error al actualizar producto: " . $conn->error . "</h3>";
    }
    $stmt->close();
    $result = consultarProductos();
}

// ELIMINAR PRODUCTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_id'])) {
    $id = intval($_POST['eliminar_id']);
    
    // Obtener la ruta de la imagen antes de eliminar
    $sql_img = "SELECT imagen FROM productos WHERE id = ?";
    $stmt_img = $conn->prepare($sql_img);
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    $producto = $result_img->fetch_assoc();
    $stmt_img->close();
    
    $sql = "DELETE FROM productos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Eliminar la imagen del servidor si existe
        if ($producto['imagen'] && file_exists($producto['imagen'])) {
            unlink($producto['imagen']);
        }
        echo "<h3 style='color:red;'>Producto eliminado correctamente.</h3>";
    } else {
        echo "<h3 style='color:red;'>Error al eliminar producto: " . $conn->error . "</h3>";
    }
    $stmt->close();
    $result = consultarProductos();
}
?>

<!-- Tabla de productos -->
<h2 style="text-align: center; margin-top: 40px;">Lista de Productos</h2>
<table border="1" cellpadding="5" cellspacing="0" style="margin-top:30px; width:100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Imagen</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Precio (COP)</th>
            <th>Cantidad</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td>
                        <?php if ($row['imagen'] && file_exists($row['imagen'])): ?>
                            <img src="<?php echo htmlspecialchars($row['imagen']); ?>" class="producto-imagen" alt="<?php echo htmlspecialchars($row['nombre']); ?>">
                        <?php else: ?>
                            <div class="sin-imagen">Sin imagen</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                    <td>$<?php echo number_format($row['precio'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['cantidad']); ?></td>
                    <td>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este producto?');">
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
            <tr><td colspan="7" style="text-align:center;">No hay productos registrados</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>