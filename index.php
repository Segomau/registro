<?php
include 'conexion.php';

// Carpeta donde se guardan las imágenes
$targetDir = "imagenes/";

// AGREGAR NUEVO REGISTRO
if (isset($_POST['agregar'])) {
    $codigo = $_POST['codigo'];
    $precio = $_POST['precio'];
    $tipo = $_POST['tipo'];

    // Procesar imagen si existe
    $imagenNombre = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen']['tmp_name'];
        $fileName = basename($_FILES['imagen']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedExt)) {
            // Crear nombre único para evitar sobreescritura
            $imagenNombre = uniqid('joya_') . '.' . $fileExtension;
            $destPath = $targetDir . $imagenNombre;
            move_uploaded_file($fileTmpPath, $destPath);
        }
    }

    $stmt = $conn->prepare("INSERT INTO joyas (codigo_joya, precio, tipo_joyeria, imagen) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $codigo, $precio, $tipo, $imagenNombre);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit;
}

// ELIMINAR REGISTRO
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];

    // Borrar imagen asociada si existe
    $resultImg = $conn->query("SELECT imagen FROM joyas WHERE id=$id");
    if ($resultImg) {
        $rowImg = $resultImg->fetch_assoc();
        if (!empty($rowImg['imagen']) && file_exists($targetDir . $rowImg['imagen'])) {
            unlink($targetDir . $rowImg['imagen']);
        }
    }

    $conn->query("DELETE FROM joyas WHERE id=$id");
    header("Location: index.php");
    exit;
}

// ACTUALIZAR REGISTRO
if (isset($_POST['actualizar'])) {
    $id = $_POST['id'];
    $codigo = $_POST['codigo'];
    $precio = $_POST['precio'];
    $tipo = $_POST['tipo'];

    // Ver imagen vieja para borrar si suben nueva
    $resultImg = $conn->query("SELECT imagen FROM joyas WHERE id=$id");
    $rowImg = $resultImg->fetch_assoc();
    $imagenNombre = $rowImg['imagen']; // por defecto la que ya hay

    // Procesar nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen']['tmp_name'];
        $fileName = basename($_FILES['imagen']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedExt)) {
            // Borrar imagen vieja si existe
            if (!empty($imagenNombre) && file_exists($targetDir . $imagenNombre)) {
                unlink($targetDir . $imagenNombre);
            }

            $imagenNombre = uniqid('joya_') . '.' . $fileExtension;
            $destPath = $targetDir . $imagenNombre;
            move_uploaded_file($fileTmpPath, $destPath);
        }
    }

    $stmt = $conn->prepare("UPDATE joyas SET codigo_joya=?, precio=?, tipo_joyeria=?, imagen=? WHERE id=?");
    $stmt->bind_param("sdssi", $codigo, $precio, $tipo, $imagenNombre, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit;
}

// FILTRAR BÚSQUEDA
$busqueda = '';
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = $conn->real_escape_string(trim($_GET['buscar']));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Joyas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            color: #333;
        }
        .top-right-logo {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .top-right-logo img {
            height: 60px;
            width: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        form {
            margin-bottom: 30px;
        }
        label {
            display: block;
            margin-top: 12px;
            font-weight: 600;
        }
        input[type="text"],
        input[type="number"],
        input[type="search"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: border 0.3s;
        }
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="search"]:focus,
        input[type="file"]:focus {
            border-color: #2c3e50;
            outline: none;
        }
        input[type="submit"],
        button {
            margin-top: 20px;
            background-color: #2c3e50;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        input[type="submit"]:hover,
        button:hover {
            background-color: #1a242f;
        }
        a.button-cancelar {
            display: inline-block;
            margin-top: 15px;
            color: #888;
            text-decoration: none;
        }
        a.button-cancelar:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            text-align: left;
            padding: 14px;
            vertical-align: middle;
        }
        th {
            background-color: #2c3e50;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f2f4f7;
        }
        tr:hover {
            background-color: #e8ecf0;
        }
        .acciones a {
            margin-right: 10px;
            color: #1a73e8;
            text-decoration: none;
            font-weight: 500;
        }
        .acciones a:hover {
            text-decoration: underline;
        }
        /* Buscador estilo inline */
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-form input[type="search"] {
            flex-grow: 1;
        }
        /* Imagen en tabla */
        .joya-img {
            height: 50px;
            width: auto;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="top-right-logo">
        <img src="imagenes/logo.png" alt="Logo Joyería" />
    </div>

    <div class="container">
        <h2><?php echo isset($_GET['editar']) ? 'Editar Joya' : 'Manejo de inventario'; ?></h2>

        <?php if (isset($_GET['editar'])): 
            $id = $_GET['editar'];
            $result = $conn->query("SELECT * FROM joyas WHERE id=$id");
            $joya = $result->fetch_assoc();
        ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $joya['id']; ?>">
                <label>Código:</label>
                <input type="text" name="codigo" value="<?php echo $joya['codigo_joya']; ?>" required>
                <label>Precio:</label>
                <input type="number" step="0.01" name="precio" value="<?php echo $joya['precio']; ?>" required>
                <label>Tipo de Joyería:</label>
                <input type="text" name="tipo" value="<?php echo $joya['tipo_joyeria']; ?>" required>
                <label>Imagen Actual:</label>
                <?php if (!empty($joya['imagen']) && file_exists($targetDir . $joya['imagen'])): ?>
                    <img src="<?php echo $targetDir . $joya['imagen']; ?>" alt="Imagen Joya" class="joya-img">
                <?php else: ?>
                    <p>No hay imagen</p>
                <?php endif; ?>
                <label>Cambiar Imagen:</label>
                <input type="file" name="imagen" accept="image/*">
                <input type="submit" name="actualizar" value="Actualizar">
            </form>
            <a href="index.php" class="button-cancelar">Cancelar</a>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data">
                <label>Código:</label>
                <input type="text" name="codigo" required>
                <label>Precio:</label>
                <input type="number" step="0.01" name="precio" required>
                <label>Tipo de Joyería:</label>
                <input type="text" name="tipo" required>
                <label>Imagen:</label>
                <input type="file" name="imagen" accept="image/*">
                <input type="submit" name="agregar" value="Agregar">
            </form>
        <?php endif; ?>

        <!-- Formulario de búsqueda -->
        <form method="get" class="search-form">
            <input type="search" name="buscar" placeholder="Buscar por código..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="index.php" style="align-self:center; margin-left:10px; color:#888; text-decoration:none;">Limpiar</a>
            <?php endif; ?>
        </form>

        <h2>Lista de Joyas</h2>
        <table>
            <tr>
                <th>Código</th>
                <th>Precio</th>
                <th>Tipo de Joyería</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
            <?php
                   if ($busqueda !== '') {
                // Buscar por código exacto o que contenga la cadena
                $sql = "SELECT * FROM joyas WHERE codigo_joya LIKE '%$busqueda%' ORDER BY id DESC";
            } else {
                $sql = "SELECT * FROM joyas ORDER BY id DESC";
            }
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['codigo_joya']}</td>
                            <td>$" . number_format($row['precio'], 2) . "</td>
                            <td>{$row['tipo_joyeria']}</td>
                            <td>";
                    if (!empty($row['imagen']) && file_exists($targetDir . $row['imagen'])) {
                        echo "<img src='{$targetDir}{$row['imagen']}' alt='Imagen Joya' class='joya-img'>";
                    } else {
                        echo "Sin imagen";
                    }
                    echo    "</td>
                            <td class='acciones'>
                                <a href='index.php?editar={$row['id']}'>Editar</a>
                                <a href='index.php?eliminar={$row['id']}' onclick=\"return confirm('¿Seguro que deseas eliminar este registro?');\">Eliminar</a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align:center; padding:20px;'>No se encontraron joyas.</td></tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>
