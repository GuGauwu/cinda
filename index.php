<?php
require_once 'funciones.php';

// Iniciar sesión para mensajes flash
session_start();

// Obtener y procesar alertas
$alertasData = obtenerAlertas();
$error = isset($alertasData['error']) ? $alertasData['error'] : null;
$alertas = [];

if (!$error) {
    $alertas = array_map(function($alerta) {
        // Mapeo de códigos a valores legibles
        $departamentos = [
            '42501' => 'SISTEMAS',
            '42502' => 'ELECTRÓNICA', 
            '42503' => 'INDUSTRIAL'
        ];
        
        $tiposAlerta = [
            'T' => 'TUTORÍA',
            'B' => 'BAJA RENDIMIENTO',
            'A' => 'ASISTENCIA'
        ];
        
        return [
            'Id' => $alerta['id'] ?? '',
            'Matricula' => $alerta['matricula'] ?? '',
            'Departamento' => $departamentos[$alerta['escuela'] ?? ''] ?? $alerta['escuela'] ?? '',
            'Semestre' => $alerta['semestre'] ?? '',
            'Alerta' => $tiposAlerta[$alerta['descripcion'] ?? ''] ?? $alerta['descripcion'] ?? '',
            'Estatus' => ($alerta['status'] ?? '') == 'A' ? 'ACTIVO' : 'INACTIVO',
            'CodDepartamento' => $alerta['escuela'] ?? '',
            'CodAlerta' => $alerta['descripcion'] ?? '',
            'CodEstatus' => $alerta['status'] ?? ''
        ];
    }, $alertasData);
}

// Limpiar parámetros GET para formulario
$editarId = $_GET['editar'] ?? '';
$matricula = $_GET['matricula'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$semestre = $_GET['semestre'] ?? '';
$alerta = $_GET['alerta'] ?? '';
$estatus = $_GET['estatus'] ?? '';
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
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje <?= $_SESSION['mensaje']['tipo'] ?>">
                <?= htmlspecialchars($_SESSION['mensaje']['texto']) ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>
        
        <section class="form-section">
            <h2><?= $editarId ? 'Editar Alerta' : 'Agregar Nueva Alerta' ?></h2>
            <form action="procesar.php" method="post" id="alertaForm">
                <input type="hidden" name="id" value="<?= htmlspecialchars($editarId) ?>">
                
                <div class="form-group">
                    <label for="matricula">Matrícula</label>
                    <input type="text" id="matricula" name="matricula" 
                           value="<?= htmlspecialchars($matricula) ?>" 
                           required pattern="[A-Za-z0-9]{7,8}" 
                           title="7 u 8 caracteres alfanuméricos">
                </div>
                
                <div class="form-group">
                    <label for="departamento">Departamento</label>
                    <select id="departamento" name="departamento" required class="styled-select">
                        <option value="">Seleccione departamento</option>
                        <option value="42501" <?= $departamento == '42501' ? 'selected' : '' ?>>SISTEMAS</option>
                        <option value="42502" <?= $departamento == '42502' ? 'selected' : '' ?>>ELECTRÓNICA</option>
                        <option value="42503" <?= $departamento == '42503' ? 'selected' : '' ?>>INDUSTRIAL</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre" required class="styled-select">
                        <option value="">Seleccione semestre</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $semestre == $i ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="alerta">Tipo de Alerta</label>
                    <select id="alerta" name="alerta" required class="styled-select">
                        <option value="">Seleccione tipo</option>
                        <option value="T" <?= $alerta == 'T' ? 'selected' : '' ?>>TUTORÍA</option>
                        <option value="B" <?= $alerta == 'B' ? 'selected' : '' ?>>BAJA RENDIMIENTO</option>
                        <option value="A" <?= $alerta == 'A' ? 'selected' : '' ?>>ASISTENCIA</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estatus">Estatus</label>
                    <select id="estatus" name="estatus" required class="styled-select">
                        <option value="">Seleccione estatus</option>
                        <option value="A" <?= $estatus == 'A' ? 'selected' : '' ?>>ACTIVO</option>
                        <option value="I" <?= $estatus == 'I' ? 'selected' : '' ?>>INACTIVO</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="accion" value="<?= $editarId ? 'actualizar' : 'crear' ?>" class="btn-primary">
                        <?= $editarId ? 'ACTUALIZAR ALERTA' : 'GUARDAR ALERTA' ?>
                    </button>
                    
                    <?php if ($editarId): ?>
                        <a href="index.php" class="btn-cancelar">CANCELAR</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
        
        <section class="table-section">
            <h2>Alertas Registradas</h2>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php elseif (empty($alertas)): ?>
                <div class="no-data">No hay alertas registradas</div>
            <?php else: ?>
                <div class="table-responsive">
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
                            <?php foreach ($alertas as $alerta): ?>
                                <tr>
                                    <td><?= htmlspecialchars($alerta['Matricula']) ?></td>
                                    <td><?= htmlspecialchars($alerta['Departamento']) ?></td>
                                    <td><?= htmlspecialchars($alerta['Semestre']) ?></td>
                                    <td><?= htmlspecialchars($alerta['Alerta']) ?></td>
                                    <td><span class="estatus <?= strtolower($alerta['Estatus']) ?>"><?= htmlspecialchars($alerta['Estatus']) ?></span></td>
                                    <td class="acciones">
                                        <a href="index.php?editar=<?= $alerta['Id'] ?>&matricula=<?= urlencode($alerta['Matricula']) ?>&departamento=<?= urlencode($alerta['CodDepartamento']) ?>&semestre=<?= urlencode($alerta['Semestre']) ?>&alerta=<?= urlencode($alerta['CodAlerta']) ?>&estatus=<?= urlencode($alerta['CodEstatus']) ?>" class="btn-editar">EDITAR</a>
                                        <a href="#" class="btn-eliminar" onclick="confirmarEliminacion('<?= $alerta['Id'] ?>')">ELIMINAR</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Validación del formulario
        document.getElementById('alertaForm').addEventListener('submit', function(e) {
            const selects = document.querySelectorAll('select[required]');
            let isValid = true;
            
            selects.forEach(select => {
                if (!select.value) {
                    select.style.borderColor = '#e74c3c';
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor complete todos los campos requeridos');
            }
        });

        // Confirmación de eliminación con AJAX
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de eliminar esta alerta?')) {
                fetch('procesar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=eliminar&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error al eliminar la alerta');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al conectar con el servidor');
                });
            }
        }

        // Resetear estilos al cambiar selección
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                if (this.value) {
                    this.style.borderColor = '';
                }
            });
        });
    </script>
</body>
</html>