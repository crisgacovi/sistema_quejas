<?php
// index.php
require_once "config.php";

// Iniciar sesión para manejar errores
session_start();

// Recuperar errores si existen
$errores = isset($_SESSION['errores']) ? $_SESSION['errores'] : [];
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];

// Limpiar los datos de la sesión
unset($_SESSION['errores']);
unset($_SESSION['form_data']);

// Consultar ciudades
$ciudades = [];
$sql = "SELECT id, nombre FROM ciudades ORDER BY nombre";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $ciudades[] = $row;
    }
}

// Consultar EPS
$eps_list = [];
$sql = "SELECT id, nombre FROM eps ORDER BY nombre";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $eps_list[] = $row;
    }
}

// Consultar tipos de queja
$tipos_queja = [];
$sql = "SELECT id, nombre FROM tipos_queja ORDER BY nombre";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tipos_queja[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Quejas y Reclamos - Servicios de Salud</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Sistema de Quejas y Reclamos</h1>
            <h2>Servicios de Salud</h2>
        </header>
        
        <main>
            <?php if (!empty($errores)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach($errores as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form id="quejaForm" action="procesar_queja.php" method="POST">
                <div class="form-section">
                    <h3>Datos Personales</h3>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre completo:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo isset($form_data['nombre']) ? htmlspecialchars($form_data['nombre']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="documento">Documento de identidad:</label>
                        <input type="text" id="documento" name="documento" value="<?php echo isset($form_data['documento']) ? htmlspecialchars($form_data['documento']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Correo electrónico:</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono de contacto:</label>
                        <input type="tel" id="telefono" name="telefono" value="<?php echo isset($form_data['telefono']) ? htmlspecialchars($form_data['telefono']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Información de la Queja</h3>
                    
                    <div class="form-group">
                        <label for="ciudad">Ciudad:</label>
                        <select id="ciudad" name="ciudad" required>
                            <option value="">Seleccione una ciudad</option>
                            <?php foreach($ciudades as $ciudad): ?>
                            <option value="<?php echo $ciudad['id']; ?>" <?php echo (isset($form_data['ciudad']) && $form_data['ciudad'] == $ciudad['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ciudad['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="eps">EPS:</label>
                        <select id="eps" name="eps" required>
                            <option value="">Seleccione una EPS</option>
                            <?php foreach($eps_list as $eps): ?>
                            <option value="<?php echo $eps['id']; ?>" <?php echo (isset($form_data['eps']) && $form_data['eps'] == $eps['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($eps['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_queja">Motivo de la queja:</label>
                        <select id="tipo_queja" name="tipo_queja" required>
                            <option value="">Seleccione un motivo</option>
                            <?php foreach($tipos_queja as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($form_data['tipo_queja']) && $form_data['tipo_queja'] == $tipo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción detallada:</label>
                        <textarea id="descripcion" name="descripcion" rows="6" required><?php echo isset($form_data['descripcion']) ? htmlspecialchars($form_data['descripcion']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Enviar Queja</button>
                    <button type="reset" class="btn-reset">Limpiar Formulario</button>
                </div>
            </form>
        </main>
        
        <footer>
            <p>&copy; 2025 Sistema de Quejas y Reclamos en Servicios de Salud</p>
        </footer>
    </div>
</body>
</html>
