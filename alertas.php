<?php
// Configuración inicial
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Función para obtener alertas desde API y archivo JSON
function obtenerAlertas() {
    $url = "https://sysweb.unach.mx/Siae/api/Alertas/Obtener";
    $alertasAPI = [];
    $alertasLocales = [];

    // Obtener alertas de la API con manejo de errores
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode === 200) {
            $alertasAPI = json_decode($response, true) ?? [];
            // Verificar estructura de la API
            if (isset($alertasAPI['lst'])) {
                $alertasAPI = $alertasAPI['lst'];
            }
        }
        curl_close($ch);
    } catch (Exception $e) {
        error_log("Error al obtener alertas de API: " . $e->getMessage());
    }

    // Obtener alertas locales con manejo de errores
    try {
        $archivo = 'alertas.json';
        if (file_exists($archivo)) {
            $contenido = file_get_contents($archivo);
            $alertasLocales = json_decode($contenido, true) ?? [];
        }
    } catch (Exception $e) {
        error_log("Error al leer archivo local: " . $e->getMessage());
    }

    return array_merge($alertasAPI, $alertasLocales);
}

// Función para guardar alertas con validación
function guardarAlerta($nuevaAlerta) {
    // Validar campos requeridos
    $camposRequeridos = ['matricula', 'departamento', 'semestre', 'alerta', 'estatus'];
    foreach ($camposRequeridos as $campo) {
        if (empty($nuevaAlerta[$campo])) {
            throw new Exception("El campo $campo es requerido");
        }
    }

    $archivo = 'alertas.json';
    $alertas = [];

    if (file_exists($archivo)) {
        $contenido = file_get_contents($archivo);
        $alertas = json_decode($contenido, true) ?? [];
    }

    // Asignar ID único si es nueva alerta
    if (!isset($nuevaAlerta['id'])) {
        $nuevaAlerta['id'] = uniqid();
    }

    $alertas[] = $nuevaAlerta;
    
    // Guardar con manejo de errores
    if (!file_put_contents($archivo, json_encode($alertas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        throw new Exception("Error al guardar en archivo");
    }

    return $nuevaAlerta['id'];
}

// Procesar POST para nueva alerta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nuevaAlerta = [
            'matricula' => strtoupper(trim($_POST['matricula'] ?? '')),
            'departamento' => $_POST['departamento'] ?? '',
            'semestre' => $_POST['semestre'] ?? '',
            'alerta' => $_POST['alerta'] ?? '',
            'estatus' => $_POST['estatus'] ?? ''
        ];

        guardarAlerta($nuevaAlerta);
        $_SESSION['mensaje'] = ['texto' => 'Alerta guardada correctamente', 'tipo' => 'exito'];
    } catch (Exception $e) {
        $_SESSION['mensaje'] = ['texto' => 'Error: ' . $e->getMessage(), 'tipo' => 'error'];
    }
    
    header('Location: alertas.php');
    exit;
}

$alertas = obtenerAlertas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Alertas UNACH</title>
    <style>
        :root {
            --color-primario: #E67E22;
            --color-secundario: #000;
            --color-exito: #2ecc71;
            --color-error: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fdf6f0;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .logo {
            height: 60px;
            margin-right: 20px;
        }
        
        h1 {
            color: var(--color-secundario);
            margin: 0;
        }
        
        .btn-primario {
            background-color: var(--color-primario);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn-primario:hover {
            background-color: #D35400;
        }
        
        .formulario {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
        }
        
        .formulario.mostrar {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: var(--color-primario);
            outline: none;
            box-shadow: 0 0 0 2px rgba(230, 126, 34, 0.2);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        th {
            background-color: var(--color-primario);
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background-color: #fff7ed;
        }
        
        .acciones {
            display: flex;
            gap: 10px;
        }
        
        .btn-accion {
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-editar {
            background-color: #3498db;
            color: white;
        }
        
        .btn-editar:hover {
            background-color: #2980b9;
        }
        
        .btn-eliminar {
            background-color: var(--color-error);
            color: white;
        }
        
        .btn-eliminar:hover {
            background-color: #c0392b;
        }
        
        .mensaje {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        
        .mensaje-exito {
            background-color: var(--color-exito);
        }
        
        .mensaje-error {
            background-color: var(--color-error);
        }
        
        .texto-centrado {
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .acciones {
                flex-direction: column;
            }
            
            .form-control {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <img src="https://www.unach.mx/images/logo_unach.png" alt="Logo UNACH" class="logo">
            <h1>Sistema de Alertas UNACH</h1>
        </header>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje mensaje-<?= $_SESSION['mensaje']['tipo'] ?>">
                <?= $_SESSION['mensaje']['texto'] ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>
        
        <button class="btn-primario" id="btnMostrarForm">Agregar Nueva Alerta</button>
        
        <div id="formulario" class="formulario">
            <form id="form-alerta" method="POST" action="alertas.php">
                <input type="hidden" name="id" id="id-alerta">
                
                <div class="form-group">
                    <label for="matricula">Matrícula</label>
                    <input type="text" id="matricula" name="matricula" class="form-control" 
                           pattern="[A-Za-z0-9]{7,8}" title="7 u 8 caracteres alfanuméricos" required>
                </div>
                
                <div class="form-group">
                    <label for="departamento">Departamento</label>
                    <select id="departamento" name="departamento" class="form-control" required>
                        <option value="">Seleccione departamento</option>
                        <option value="42501">SISTEMAS</option>
                        <option value="42502">ELECTRÓNICA</option>
                        <option value="42503">INDUSTRIAL</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre" class="form-control" required>
                        <option value="">Seleccione semestre</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="alerta">Tipo de Alerta</label>
                    <select id="alerta" name="alerta" class="form-control" required>
                        <option value="">Seleccione tipo</option>
                        <option value="T">TUTORÍA</option>
                        <option value="B">BAJA RENDIMIENTO</option>
                        <option value="A">ASISTENCIA</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estatus">Estatus</label>
                    <select id="estatus" name="estatus" class="form-control" required>
                        <option value="">Seleccione estatus</option>
                        <option value="A">ACTIVO</option>
                        <option value="I">INACTIVO</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primario">Guardar Alerta</button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Departamento</th>
                    <th>Semestre</th>
                    <th>Alerta</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($alertas)): ?>
                    <?php foreach ($alertas as $alerta): ?>
                        <?php if (!empty($alerta['matricula'])): ?>
                            <tr data-id="<?= $alerta['id'] ?? '' ?>">
                                <td><?= htmlspecialchars($alerta['matricula']) ?></td>
                                <td>
                                    <?= match($alerta['departamento'] ?? '') {
                                        '42501' => 'SISTEMAS',
                                        '42502' => 'ELECTRÓNICA',
                                        '42503' => 'INDUSTRIAL',
                                        default => htmlspecialchars($alerta['departamento'] ?? '')
                                    } ?>
                                </td>
                                <td><?= htmlspecialchars($alerta['semestre'] ?? '') ?></td>
                                <td>
                                    <?= match($alerta['alerta'] ?? '') {
                                        'T' => 'TUTORÍA',
                                        'B' => 'BAJA RENDIMIENTO',
                                        'A' => 'ASISTENCIA',
                                        default => htmlspecialchars($alerta['alerta'] ?? '')
                                    } ?>
                                </td>
                                <td>
                                    <span class="estatus-<?= strtolower($alerta['estatus'] ?? '') ?>">
                                        <?= match($alerta['estatus'] ?? '') {
                                            'A' => 'ACTIVO',
                                            'I' => 'INACTIVO',
                                            default => htmlspecialchars($alerta['estatus'] ?? '')
                                        } ?>
                                    </span>
                                </td>
                                <td class="acciones">
                                    <a href="#" class="btn-accion btn-editar" onclick="editarAlerta(event, this)">Editar</a>
                                    <a href="#" class="btn-accion btn-eliminar" onclick="eliminarAlerta(event, this)">Eliminar</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="texto-centrado">No hay alertas registradas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Mostrar/ocultar formulario
        document.getElementById('btnMostrarForm').addEventListener('click', function() {
            const formulario = document.getElementById('formulario');
            formulario.classList.toggle('mostrar');
            document.getElementById('form-alerta').reset();
            document.getElementById('id-alerta').value = '';
        });

        // Editar alerta
        function editarAlerta(event, elemento) {
            event.preventDefault();
            
            const fila = elemento.closest('tr');
            const celdas = fila.querySelectorAll('td');
            
            document.getElementById('matricula').value = celdas[0].textContent;
            
            // Obtener código de departamento
            const deptText = celdas[1].textContent;
            const deptSelect = document.getElementById('departamento');
            for (let i = 0; i < deptSelect.options.length; i++) {
                if (deptSelect.options[i].text === deptText) {
                    deptSelect.selectedIndex = i;
                    break;
                }
            }
            
            document.getElementById('semestre').value = celdas[2].textContent;
            
            // Obtener código de alerta
            const alertaText = celdas[3].textContent;
            const alertaSelect = document.getElementById('alerta');
            for (let i = 0; i < alertaSelect.options.length; i++) {
                if (alertaSelect.options[i].text === alertaText) {
                    alertaSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Obtener código de estatus
            const estatusText = celdas[4].textContent.trim();
            const estatusSelect = document.getElementById('estatus');
            for (let i = 0; i < estatusSelect.options.length; i++) {
                if (estatusSelect.options[i].text === estatusText) {
                    estatusSelect.selectedIndex = i;
                    break;
                }
            }
            
            document.getElementById('id-alerta').value = fila.dataset.id;
            document.getElementById('formulario').classList.add('mostrar');
            document.getElementById('form-alerta').action = 'editarAlerta.php';
            document.querySelector('#form-alerta button[type="submit"]').textContent = 'Actualizar Alerta';
            
            // Scroll al formulario
            document.getElementById('formulario').scrollIntoView({ behavior: 'smooth' });
        }

        // Eliminar alerta
        function eliminarAlerta(event, elemento) {
            event.preventDefault();
            
            if (confirm('¿Estás seguro de eliminar esta alerta?')) {
                const fila = elemento.closest('tr');
                const id = fila.dataset.id;
                
                // Enviar solicitud de eliminación
                fetch('eliminarAlerta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + encodeURIComponent(id)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fila.remove();
                        // Mostrar mensaje de éxito
                        const mensaje = document.createElement('div');
                        mensaje.className = 'mensaje mensaje-exito';
                        mensaje.textContent = data.message;
                        document.querySelector('.container').prepend(mensaje);
                        
                        // Ocultar mensaje después de 3 segundos
                        setTimeout(() => mensaje.remove(), 3000);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la alerta');
                });
            }
        }
    </script>
</body>
</html>