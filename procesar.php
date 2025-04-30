<?php
require_once 'funciones.php';

// Configuración inicial
session_start();
header('Content-Type: application/json'); // Para respuestas AJAX

// Función para enviar respuestas estandarizadas
function enviarRespuesta($success, $message = '', $data = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode($response);
        exit;
    }
    
    $_SESSION['mensaje'] = [
        'texto' => $message,
        'tipo' => $success ? 'exito' : 'error'
    ];
    
    header('Location: index.php');
    exit;
}

// Procesamiento principal
try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    // Determinar acción
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
        case 'actualizar':
            // Validar y sanitizar datos
            $datos = [
                'Matricula' => strtoupper(trim($_POST['matricula'] ?? '')),
                'Departamento' => $_POST['departamento'] ?? '',
                'Semestre' => $_POST['semestre'] ?? '',
                'Alerta' => $_POST['alerta'] ?? '',
                'Estatus' => $_POST['estatus'] ?? ''
            ];
            
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $datos['Id'] = $_POST['id'];
            }
            
            // Validar campos requeridos
            $camposRequeridos = ['Matricula', 'Departamento', 'Semestre', 'Alerta', 'Estatus'];
            foreach ($camposRequeridos as $campo) {
                if (empty($datos[$campo])) {
                    throw new Exception("El campo $campo es requerido", 400);
                }
            }
            
            // Validar formato de matrícula
            if (!preg_match('/^[A-Z0-9]{7,8}$/', $datos['Matricula'])) {
                throw new Exception('La matrícula debe tener 7-8 caracteres alfanuméricos', 400);
            }
            
            // Procesar la alerta
            $resultado = gestionarAlerta($datos, $accion === 'actualizar');
            
            if ($resultado === true) {
                enviarRespuesta(true, $accion === 'crear' ? 'Alerta creada correctamente' : 'Alerta actualizada correctamente');
            } else {
                throw new Exception(is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Error al procesar la alerta');
            }
            break;
            
        case 'eliminar':
            // Validar ID
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                throw new Exception('ID de alerta no proporcionado', 400);
            }
            
            // Eliminar alerta
            if (eliminarAlerta($id)) {
                enviarRespuesta(true, 'Alerta eliminada correctamente');
            } else {
                throw new Exception('Error al eliminar la alerta');
            }
            break;
            
        default:
            throw new Exception('Acción no reconocida', 400);
    }
    
} catch (Exception $e) {
    error_log('Error en procesar.php: ' . $e->getMessage());
    enviarRespuesta(false, $e->getMessage(), ['code' => $e->getCode()]);
}