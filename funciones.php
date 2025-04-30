<?php
// Configuración de la API
define('API_URL', 'https://sysweb.unach.mx/Siae/api/Alertas');

// Función para obtener todas las alertas con manejo mejorado de errores
function obtenerAlertas() {
    $url = API_URL . '/Obtener';
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception('Error de conexión: ' . curl_error($ch));
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception("La API respondió con código $httpCode");
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
        }
        
        if (!isset($data['lst']) || !is_array($data['lst'])) {
            throw new Exception('Estructura de datos inesperada');
        }
        
        return $data['lst'];
        
    } catch (Exception $e) {
        error_log('Error en obtenerAlertas: ' . $e->getMessage());
        return ['error' => 'No se pudieron cargar las alertas. ' . $e->getMessage()];
    } finally {
        if (isset($ch)) curl_close($ch);
    }
}

// Función para crear/actualizar alertas
function gestionarAlerta($datos, $esActualizacion = false) {
    $url = $esActualizacion ? API_URL . '/Actualizar' : API_URL . '/Crear';
    
    try {
        $payload = [
            'matricula' => $datos['Matricula'],
            'escuela' => $datos['Departamento'],
            'semestre' => $datos['Semestre'],
            'descripcion' => $datos['Alerta'],
            'status' => $datos['Estatus']
        ];
        
        if ($esActualizacion) {
            $payload['id'] = $datos['Id'];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $esActualizacion ? 'PUT' : 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            throw new Exception('Error de conexión: ' . curl_error($ch));
        }
        
        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['message'] ?? 'Error desconocido';
            throw new Exception("Error en la API ($httpCode): $errorMsg");
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('Error en gestionarAlerta: ' . $e->getMessage());
        return false;
    } finally {
        if (isset($ch)) curl_close($ch);
    }
}

// Función para eliminar alertas
function eliminarAlerta($id) {
    $url = API_URL . '/Eliminar/' . $id;
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            throw new Exception("Error al eliminar (Código $httpCode)");
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('Error en eliminarAlerta: ' . $e->getMessage());
        return false;
    } finally {
        if (isset($ch)) curl_close($ch);
    }
}
?>