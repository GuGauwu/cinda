<?php
require_once 'funciones.php';

// Habilitar visualización de errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar los datos de entrada
    $datos = [
        'Matricula' => strtoupper(trim($_POST['matricula'])),
        'Departamento' => $_POST['departamento'] ?? '',
        'Semestre' => $_POST['semestre'] ?? '',
        'Alerta' => $_POST['alerta'] ?? '',
        'Estatus' => $_POST['estatus'] ?? ''
    ];

    // Validación básica de campos requeridos
    $camposRequeridos = ['Matricula', 'Departamento', 'Semestre', 'Alerta', 'Estatus'];
    $camposFaltantes = array_filter($camposRequeridos, fn($campo) => empty($datos[$campo]));
    
    if (!empty($camposFaltantes)) {
        header('Location: index.php?mensaje=' . urlencode('Faltan campos requeridos: ' . implode(', ', $camposFaltantes)) . '&error=1');
        exit;
    }

    // Si es una edición, agregar el ID
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $datos['Id'] = $_POST['id'];
    }

    // Determinar la acción (crear o actualizar)
    $accion = $_POST['accion'] ?? '';
    $exito = false;
    $mensaje = 'Acción no válida';

    try {
        if ($accion === 'crear') {
            $exito = gestionarAlerta($datos);
            $mensaje = $exito ? 'Alerta creada correctamente' : 'Error al crear la alerta (verifique los datos)';
        } elseif ($accion === 'actualizar') {
            $exito = gestionarAlerta($datos, true);
            $mensaje = $exito ? 'Alerta actualizada correctamente' : 'Error al actualizar la alerta';
        }
    } catch (Exception $e) {
        $mensaje = 'Error en el servidor: ' . $e->getMessage();
        error_log("Error en procesar.php: " . $e->getMessage());
    }

    // Redireccionar con el resultado
    header('Location: index.php?mensaje=' . urlencode($mensaje) . ($exito ? '' : '&error=1'));
    exit;
}

// Si no es POST o no hay acción válida, redirigir
header('Location: index.php?mensaje=' . urlencode('Acción no válida') . '&error=1');
exit;
?>