<?php
header('Content-Type: application/json');
require_once 'funciones.php';

$response = ['success' => false, 'message' => 'Acción no válida'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $id = $_POST['id'];
        $archivo = 'alertas.json';
        $alertas = [];
        
        if (file_exists($archivo)) {
            $contenido = file_get_contents($archivo);
            $alertas = json_decode($contenido, true) ?? [];
        }

        // Filtrar para eliminar la alerta
        $alertas = array_filter($alertas, fn($alerta) => ($alerta['id'] ?? '') !== $id);
        
        if (file_put_contents($archivo, json_encode(array_values($alertas), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $response = ['success' => true, 'message' => 'Alerta eliminada correctamente'];
        } else {
            $response = ['success' => false, 'message' => 'Error al guardar los cambios'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
?>