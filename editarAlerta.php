<?php
require_once 'funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $alertaActualizada = [
            'id' => $_POST['id'],
            'matricula' => strtoupper(trim($_POST['matricula'] ?? '')),
            'departamento' => $_POST['departamento'] ?? '',
            'semestre' => $_POST['semestre'] ?? '',
            'alerta' => $_POST['alerta'] ?? '',
            'estatus' => $_POST['estatus'] ?? ''
        ];

        // Leer alertas existentes
        $archivo = 'alertas.json';
        $alertas = [];
        
        if (file_exists($archivo)) {
            $contenido = file_get_contents($archivo);
            $alertas = json_decode($contenido, true) ?? [];
        }

        // Buscar y actualizar la alerta
        $encontrada = false;
        foreach ($alertas as &$alerta) {
            if (($alerta['id'] ?? '') === $alertaActualizada['id']) {
                $alerta = array_merge($alerta, $alertaActualizada);
                $encontrada = true;
                break;
            }
        }

        if ($encontrada) {
            file_put_contents($archivo, json_encode($alertas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $_SESSION['mensaje'] = ['texto' => 'Alerta actualizada correctamente', 'tipo' => 'exito'];
        } else {
            $_SESSION['mensaje'] = ['texto' => 'No se encontró la alerta para actualizar', 'tipo' => 'error'];
        }
    } catch (Exception $e) {
        $_SESSION['mensaje'] = ['texto' => 'Error: ' . $e->getMessage(), 'tipo' => 'error'];
    }
}

header('Location: alertas.php');
exit;
?>