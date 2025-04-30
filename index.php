<?php
require_once 'funciones.php';

$alertasData = obtenerAlertas();
$error = null;
$alertas = [];

if (isset($alertasData['error'])) {
    $error = $alertasData['error'];
} else {
    $alertas = array_map(function($alerta) {
        $departamentos = [
            '42501' => 'SISTEMAS',
            '42502' => 'ELECTRÓNICA', 
            '42503' => 'INDUSTRIAL'
        ];
        
        $alertasTipos = [
            'T' => 'TUTORÍA',
            'B' => 'BAJA RENDIMIENTO',
            'A' => 'ASISTENCIA'
        ];
        
        return [
            'Id' => $alerta['id'],
            'Matricula' => $alerta['matricula'],
            'Departamento' => $departamentos[$alerta['escuela']] ?? $alerta['escuela'],
            'Semestre' => $alerta['semestre'],
            'Alerta' => $alertasTipos[$alerta['descripcion']] ?? $alerta['descripcion'],
            'Estatus' => $alerta['status'] == 'A' ? 'ACTIVO' : 'INACTIVO',
            'CodDepartamento' => $alerta['escuela'],
            'CodAlerta' => $alerta['descripcion'],
            'CodEstatus' => $alerta['status']
        ];
    }, $alertasData);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Alertas - UNACH</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><img src="https://www.unach.mx/images/logo_unach.png" alt="Logo UNACH" class="logo"> Lista de Alertas</h1>
        </header>
        
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="mensaje <?= isset($_GET['error']) ? 'error' : 'exito' ?>">
                <?= htmlspecialchars(urldecode($_GET['mensaje'])) ?>
            </div>
        <?php endif; ?>
        
        <section class="form-section">
            <h2><?= isset($_GET['editar']) ? 'Editar Alerta' : 'Agregar Nueva Alerta' ?></h2>
            <form action="procesar.php" method="post" id="alertaForm">
                <?php if (isset($_GET['editar'])): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['editar']) ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="matricula">Matrícula</label>
                    <input type="text" id="matricula" name="matricula" 
                           value="<?= isset($_GET['matricula']) ? htmlspecialchars($_GET['matricula']) : '' ?>" 
                           required pattern="[A-Za-z0-9]{7,8}" title="7 u 8 caracteres alfanuméricos">
                </div>
                
                <div class="form-group">
                    <label for="departamento">Departamento</label>
                    <select id="departamento" name="departamento" required class="styled-select">
                        <option value="">Seleccione departamento</option>
                        <option value="42501" <?= (isset($_GET['departamento']) && $_GET['departamento'] == '42501') ? 'selected' : '' ?>>SISTEMAS</option>
                        <option value="42502" <?= (isset($_GET['departamento']) && $_GET['departamento'] == '42502') ? 'selected' : '' ?>>ELECTRÓNICA</option>
                        <option value="42503" <?= (isset($_GET['departamento']) && $_GET['departamento'] == '42503') ? 'selected' : '' ?>>INDUSTRIAL</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre" required class="styled-select">
                        <option value="">Seleccione semestre</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= (isset($_GET['semestre']) && $_GET['semestre'] == $i) ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="alerta">Tipo de Alerta</label>
                    <select id="alerta" name="alerta" required class="styled-select">
                        <option value="">Seleccione tipo</option>
                        <option value="T" <?= (isset($_GET['alerta']) && $_GET['alerta'] == 'T') ? 'selected' : '' ?>>TUTORÍA</option>
                        <option value="B" <?= (isset($_GET['alerta']) && $_GET['alerta'] == 'B') ? 'selected' : '' ?>>BAJA RENDIMIENTO</option>
                        <option value="A" <?= (isset($_GET['alerta']) && $_GET['alerta'] == 'A') ? 'selected' : '' ?>>ASISTENCIA</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estatus">Estatus</label>
                    <select id="estatus" name="estatus" required class="styled-select">
                        <option value="">Seleccione estatus</option>
                        <option value="A" <?= (isset($_GET['estatus']) && $_GET['estatus'] == 'A') ? 'selected' : '' ?>>ACTIVO</option>
                        <option value="I" <?= (isset($_GET['estatus']) && $_GET['estatus'] == 'I') ? 'selected' : '' ?>>INACTIVO</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="accion" value="<?= isset($_GET['editar']) ? 'actualizar' : 'crear' ?>" class="btn-primary">
                        <?= isset($_GET['editar']) ? 'ACTUALIZAR ALERTA' : 'GUARDAR ALERTA' ?>
                    </button>
                    
                    <?php if (isset($_GET['editar'])): ?>
                        <a href="index.php" class="btn-cancelar">CANCELAR</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
        
        <section class="table-section">
            <h2>Alertas Registradas</h2>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>MATRÍCULA</th>
                            <th>DEPARTAMENTO</th>
                            <th>SEMESTRE</th>
                            <th>ALERTA</th>
                            <th>ESTATUS</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alertas)): ?>
                            <tr>
                                <td colspan="6" class="no-data">No hay alertas registradas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($alertas as $alerta): ?>
                                <tr>
                                    <td><?= htmlspecialchars($alerta['Matricula']) ?></td>
                                    <td><?= htmlspecialchars($alerta['Departamento']) ?></td>
                                    <td><?= htmlspecialchars($alerta['Semestre']) ?></td>
                                    <td><?= htmlspecialchars($alerta['Alerta']) ?></td>
                                    <td><span class="estatus <?= strtolower($alerta['Estatus']) ?>"><?= htmlspecialchars($alerta['Estatus']) ?></span></td>
                                    <td class="acciones">
                                        <a href="index.php?editar=<?= $alerta['Id'] ?>&matricula=<?= urlencode($alerta['Matricula']) ?>&departamento=<?= urlencode($alerta['CodDepartamento']) ?>&semestre=<?= urlencode($alerta['Semestre']) ?>&alerta=<?= urlencode($alerta['CodAlerta']) ?>&estatus=<?= urlencode($alerta['CodEstatus']) ?>" class="btn-editar">EDITAR</a>
                                        <a href="procesar.php?eliminar=<?= $alerta['Id'] ?>" class="btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar esta alerta?')">ELIMINAR</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Validación del formulario
        document.getElementById('alertaForm').addEventListener('submit', function(e) {
            const selects = document.querySelectorAll('select.styled-select');
            let isValid = true;
            
            selects.forEach(select => {
                if (!select.value) {
                    select.style.borderColor = '#e74c3c';
                    isValid = false;
                } else {
                    select.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor complete todos los campos requeridos');
            }
        });
    </script>
</body>
</html>